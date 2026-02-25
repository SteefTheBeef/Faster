<?php
class BestRaces {
    /**
     * If current challenge UID is configured for "quali best race",
     * load best scores file and build a scoreboard-like players array for THIS map.
     *
     * Returns array of players (numerically indexed), or empty array on any failure.
     */
    static function umPanelBuildPlayersFromBestScoresForCurrentMap($challengeInfo, $qualiBestRacesConfig) {
        if (!is_array($challengeInfo) || !is_object($qualiBestRacesConfig)) return array();
        if (!isset($challengeInfo['UId'])) return array();

        $uid = (string)$challengeInfo['UId'];
        if ($uid === '') return array();

        // 1) check if current UID is in config maps
        $isConfigured = false;
        if (isset($qualiBestRacesConfig->maps) && is_array($qualiBestRacesConfig->maps)) {
            foreach ($qualiBestRacesConfig->maps as $m) {
                if (is_object($m) && isset($m->id) && (string)$m->id === $uid) {
                    $isConfigured = true;
                    break;
                }
            }
        }
        if (!$isConfigured) return array();


        // 2) open the correct bestScores file for this environment + uid
        $env = isset($challengeInfo['Environnement']) ? (string)$challengeInfo['Environnement'] : 'Unknown';
        $envSafe = preg_replace('/[^a-zA-Z0-9_-]+/', '', $env);
        if ($envSafe === '') $envSafe = 'Unknown';

        $uidSafe = preg_replace('/[^A-Za-z0-9_-]/', '', $uid);
        if ($uidSafe === '') return array();

        $filePath = 'fastlog/um/bestRaces.' . $envSafe . '.' . $uidSafe . '.txt';
        console($filePath);

        // 3) parse and extract players for the current MapId section
        $mapId = (string)getChallengeID($challengeInfo);
        if ($mapId === '') return array();

        $all = self::parseBestRacesFile($filePath);
        if (!isset($all[$mapId]) || !isset($all[$mapId]['players']) || !is_array($all[$mapId]['players'])) {
            return array();
        }

        // parseBestRacesFile returns players keyed by login; panel expects numeric array
        return array_values($all[$mapId]['players']);
    }
    static function updateBestRacesFile($finishedPlayers, $challengeInfo) {
        if (!is_array($finishedPlayers) || count($finishedPlayers) < 1) return;

        $env = isset($challengeInfo['Environnement']) ? (string)$challengeInfo['Environnement'] : 'Unknown';
        $envSafe = preg_replace('/[^a-zA-Z0-9_-]+/', '', $env);
        if ($envSafe === '') $envSafe = 'Unknown';

        $dir = 'fastlog/um';

        $uid = preg_replace('/[^A-Za-z0-9_-]/', '', (string)$challengeInfo['UId']);
        $filePath = $dir . '/bestScores.' . $envSafe . '.' . $uid . '.txt';

        // Use shared file helper for mkdir/touch logic.
        if (class_exists('FastFile')) {
            FastFile::ensureFile($filePath);
        } else {
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            if (!file_exists($filePath)) {
                $h = @fopen($filePath, 'a');
                if ($h) fclose($h);
            }
        }

        $mapId = (string)getChallengeID($challengeInfo);
        $mapName = isset($challengeInfo['Name']) ? stripColors($challengeInfo['Name']) : '';
        $mapAuthor = isset($challengeInfo['Author']) ? stripColors($challengeInfo['Author']) : '';

        $data = self::parseBestRacesFile($filePath);

        if (!isset($data[$mapId])) {
            $data[$mapId] = array(
                'meta' => array(
                    'Environment' => $env,
                    'MapId' => $mapId,
                    'MapName' => $mapName,
                    'Author' => $mapAuthor,
                ),
                'players' => array(), // login => row
            );
        } else {
            if (!isset($data[$mapId]['meta'])) $data[$mapId]['meta'] = array();
            $data[$mapId]['meta']['Environment'] = $env;
            $data[$mapId]['meta']['MapId'] = $mapId;
            $data[$mapId]['meta']['MapName'] = $mapName;
            $data[$mapId]['meta']['Author'] = $mapAuthor;
        }

        foreach ($finishedPlayers as $p) {
            if (!is_array($p)) continue;

            $login = isset($p['Login']) ? stripColors($p['Login']) : '';
            if ($login === '') continue;

            $lap = isset($p['Lap']) ? (int)$p['Lap'] : 0;
            $check = isset($p['Check']) ? (int)$p['Check'] : 0;
            $timeMs = isset($p['Time']) ? (int)$p['Time'] : 0;
            $bestLapMs = isset($p['BestLap']) ? (int)$p['BestLap'] : 0;

            if ($timeMs <= 0) continue;

            $newRow = array(
                'Login' => $login,
                'Lap' => $lap,
                'Check' => $check,
                'TimeMs' => $timeMs,
                'BestLapMs' => $bestLapMs,
                'UpdatedAt' => date('Y-m-d H:i:s'),
            );

            $oldRow = isset($data[$mapId]['players'][$login]) ? $data[$mapId]['players'][$login] : null;
            if ($oldRow === null) {
                $data[$mapId]['players'][$login] = $newRow;
                continue;
            }

            $oldCheck = isset($oldRow['Check']) ? (int)$oldRow['Check'] : 0;
            $oldTimeMs = isset($oldRow['TimeMs']) ? (int)$oldRow['TimeMs'] : 0;

            $isImproved = false;
            if ($check > $oldCheck) {
                $isImproved = true;
            } elseif ($check === $oldCheck && ($oldTimeMs <= 0 || $timeMs < $oldTimeMs)) {
                $isImproved = true;
            }

            if ($isImproved) {
                $data[$mapId]['players'][$login] = $newRow;
            }
        }

        foreach ($data as $mId => $section) {
            if (!isset($data[$mId]['players']) || !is_array($data[$mId]['players'])) continue;

            $rows = array_values($data[$mId]['players']);
            usort($rows, 'bestScoresCompare');
            $rekey = array();
            foreach ($rows as $r) {
                $rekey[$r['Login']] = $r;
            }
            $data[$mId]['players'] = $rekey;
        }

        self::writeBestRacesFile($filePath, $data);
    }

    /**
     * File format (multi-map, easy to parse):
     * ### MAP,<Environment>,<MapId>,<MapName>,<Author>
     * Login,Check,Lap,TimeMs,Time,BestLapMs,BestLap,UpdatedAt
     */
    private static function parseBestRacesFile($filePath) {
        $data = array();

        $lines = array();
        if (class_exists('FastFile')) {
            $lines = FastFile::readLines($filePath);
        } else {
            if (!file_exists($filePath)) return $data;
            $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
            if ($lines === false || !is_array($lines)) return $data;
        }

        if (!is_array($lines) || count($lines) < 1) return $data;

        $currentMapId = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;

            if (strpos($line, '### MAP,') === 0) {
                $parts = explode(',', $line, 5);
                $env = isset($parts[1]) ? (string)$parts[1] : '';
                $mapId = isset($parts[2]) ? (string)$parts[2] : '';
                $mapName = isset($parts[3]) ? (string)$parts[3] : '';
                $author = isset($parts[4]) ? (string)$parts[4] : '';

                $currentMapId = $mapId;
                if ($currentMapId !== '') {
                    $data[$currentMapId] = array(
                        'meta' => array(
                            'Environment' => $env,
                            'MapId' => $mapId,
                            'MapName' => $mapName,
                            'Author' => $author,
                        ),
                        'players' => array(),
                    );
                }
                continue;
            }

            if (strpos($line, 'Login,Check,') === 0) {
                continue;
            }

            if ($currentMapId === null || $currentMapId === '') continue;

            $cols = explode(',', $line);
            if (count($cols) < 4) continue;

            $login = isset($cols[0]) ? (string)$cols[0] : '';
            if ($login === '') continue;

            $check = isset($cols[1]) ? (int)$cols[1] : 0;
            $lap = isset($cols[2]) ? (int)$cols[2] : 0;
            $timeMs = isset($cols[3]) ? (int)$cols[3] : 0;

            $bestLapMs = isset($cols[5]) ? (int)$cols[5] : 0;
            $updatedAt = isset($cols[7]) ? (string)$cols[7] : '';

            $data[$currentMapId]['players'][$login] = array(
                'Login' => $login,
                'Check' => $check,
                'Lap' => $lap,
                'TimeMs' => $timeMs,
                'BestLapMs' => $bestLapMs,
                'UpdatedAt' => $updatedAt,
            );
        }

        return $data;
    }

    private static function writeBestRacesFile($filePath, $data) {
        $out = '';
        $out .= "# UM Best Scores\n";
        $out .= "# UpdatedAt=" . date('Y-m-d H:i:s') . "\n";
        $out .= "# Sort: Check DESC, then Time ASC\n\n";

        foreach ($data as $mapId => $section) {
            $meta = isset($section['meta']) ? $section['meta'] : array();
            $env = isset($meta['Environment']) ? (string)$meta['Environment'] : '';
            $mapName = isset($meta['MapName']) ? (string)$meta['MapName'] : '';
            $author = isset($meta['Author']) ? (string)$meta['Author'] : '';

            $out .= "### MAP," . $env . "," . $mapId . "," . $mapName . "," . $author . "\n";
            $out .= "Login,Check,Lap,TimeMs,Time,BestLapMs,BestLap,UpdatedAt\n";

            $players = isset($section['players']) ? $section['players'] : array();
            foreach ($players as $row) {
                $login = isset($row['Login']) ? (string)$row['Login'] : '';
                $check = isset($row['Check']) ? (int)$row['Check'] : 0;
                $lap = isset($row['Lap']) ? (int)$row['Lap'] : 0;
                $timeMs = isset($row['TimeMs']) ? (int)$row['TimeMs'] : 0;
                $bestLapMs = isset($row['BestLapMs']) ? (int)$row['BestLapMs'] : 0;
                $updatedAt = isset($row['UpdatedAt']) ? (string)$row['UpdatedAt'] : '';

                $timeStr = $timeMs > 0 ? MwTimeToString($timeMs) : '';
                $bestLapStr = $bestLapMs > 0 ? MwTimeToString($bestLapMs) : '';

                $out .= $login . "," . $check . "," . $lap . "," . $timeMs . "," . $timeStr . "," . $bestLapMs . "," . $bestLapStr . "," . $updatedAt . "\n";
            }
            $out .= "\n";
        }

        if (class_exists('FastFile')) {
            FastFile::atomicWrite($filePath, $out, true);
            return;
        }

        // atomic-ish write with lock (legacy fallback)
        $tmp = $filePath . '.tmp';
        $h = @fopen($tmp, 'wb');
        if (!$h) return;

        if (@flock($h, LOCK_EX)) {
            fwrite($h, $out);
            fflush($h);
            flock($h, LOCK_UN);
        } else {
            fwrite($h, $out);
        }
        fclose($h);

        @rename($tmp, $filePath);
    }

    static function buildQualificationRankingsAllMaps($qualiBestRacesConfig, $nickMap) {
        $byEnvLogin = self::buildQualificationScoresAllMaps($qualiBestRacesConfig, $nickMap);
        return self::normalizeEnvLoginMapToRankedLists($byEnvLogin);
    }
    /**
     * Converts env => (login => row) into env => numeric ranked list.
     * Sort: Score DESC, MapsPlayed DESC, Login ASC.
     */
    private static function normalizeEnvLoginMapToRankedLists($byEnvLogin) {
        if (!is_array($byEnvLogin) || count($byEnvLogin) < 1) return array();

        $out = array();
        foreach ($byEnvLogin as $env => $loginMap) {
            if (!is_array($loginMap) || count($loginMap) < 1) {
                $out[$env] = array();
                continue;
            }

            $list = array_values($loginMap);

            usort($list, array('BestRaces', 'compareQualificationRows'));

            $rank = 1;
            $count = count($list);
            for ($i = 0; $i < $count; $i++) {
                if (!isset($list[$i]) || !is_array($list[$i])) continue;
                $list[$i]['Rank'] = $rank;
                $rank++;
            }

            $out[$env] = $list;
        }

        return $out;
    }

    private static function compareQualificationRows($a, $b) {
        $aScore = isset($a['Score']) ? (int)$a['Score'] : 0;
        $bScore = isset($b['Score']) ? (int)$b['Score'] : 0;
        if ($aScore > $bScore) return -1;
        if ($aScore < $bScore) return 1;

        // Tie-breaker: more maps played first (optional but usually nice)
        $aMp = isset($a['MapsPlayed']) ? (int)$a['MapsPlayed'] : 0;
        $bMp = isset($b['MapsPlayed']) ? (int)$b['MapsPlayed'] : 0;
        if ($aMp > $bMp) return -1;
        if ($aMp < $bMp) return 1;

        $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
        $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
        return strcmp($aLogin, $bLogin);
    }
    /**
     * Build aggregated qualification scores across ALL configured maps.
     *
     * Output structure:
     *   array(
     *     'Rally' => array(
     *        'login1' => array('Login'=>'login1','Score'=>123,'MapsPlayed'=>2,'NickName'=>...),
     *        ...
     *     ),
     *     'Bay' => array( ... ),
     *   )
     *
     * Notes:
     * - Uses $qualiBestRacesConfig->pointsDistribution for each map ranking.
     * - Tries to locate bestScores files by UID using glob(): fastlog/um/bestScores.*.<uid>.txt
     * - If a file contains multiple MapId sections, you can optionally specify $m->mapId (or $m->MapId) in config.
     */
    static function buildQualificationScoresAllMaps(UMConfigEntry $qualiBestRacesConfig, $nickMap) {
        $maps = $qualiBestRacesConfig->maps;
        $pointsDistribution = $qualiBestRacesConfig->pointsDistribution;

        $out = array(); // env => login => row

        foreach ($maps as $m) {
            $uid = $m->id;
            $uidSafe = preg_replace('/[^A-Za-z0-9_-]/', '', $uid);
            if ($uidSafe === '') continue;

            // Optional: if config provides a specific MapId section to use.
            $wantedMapId = '';
            if (isset($m->mapId)) $wantedMapId = (string)$m->mapId;
            elseif (isset($m->MapId)) $wantedMapId = (string)$m->MapId;

            // Find all matching env files for this UID (usually 1, but robust).
            $pattern = 'fastlog/um/bestRaces.*.' . $uidSafe . '.txt';
            $files = glob($pattern);
            if ($files === false || !is_array($files) || count($files) < 1) continue;

            foreach ($files as $filePath) {
                if (!is_string($filePath) || $filePath === '') continue;
                if (!file_exists($filePath)) continue;

                $envSafe = self::extractEnvFromBestRacesFilename($filePath);
                if ($envSafe === '') $envSafe = 'Unknown';

                $all = self::parseBestRacesFile($filePath);
                if (!is_array($all) || count($all) < 1) continue;

                $mapIdToUse = self::pickMapIdFromParsedBestRaces($all, $wantedMapId);
                if ($mapIdToUse === '') continue;

                if (!isset($all[$mapIdToUse]['players']) || !is_array($all[$mapIdToUse]['players'])) continue;

                // Ensure numeric ranking list.
                $ranking = array_values($all[$mapIdToUse]['players']);

                // Apply rank->points and aggregate per player login.
                $count = count($ranking);
                for ($i = 0; $i < $count; $i++) {
                    if (!isset($ranking[$i]) || !is_array($ranking[$i])) continue;

                    $login = isset($ranking[$i]['Login']) ? stripColors($ranking[$i]['Login']) : '';
                    if ($login === '') continue;

                    $rank = $i + 1;
                    $points = self::pointsForRank($pointsDistribution, $i, $rank);

                    if (!isset($out[$envSafe])) $out[$envSafe] = array();
                    if (!isset($out[$envSafe][$login]) || !is_array($out[$envSafe][$login])) {
                        $out[$envSafe][$login] = array(
                            'Login' => $login,
                            'Score' => 0,
                            'MapsPlayed' => 0,
                            // Optional debug / introspection fields:
                            'PerMap' => array(), // uidSafe => points (or mapId => points)
                        );
                    }

                    $out[$envSafe][$login]['Score'] += $points;
                    $out[$envSafe][$login]['MapsPlayed'] += 1;
                    $out[$envSafe][$login]['PerMap'][$uidSafe] = $points;

                    // Merge nickname once here (no need to run UmPlayers::mergeNicknamesIntoPlayersList on numeric arrays).
                    if (is_array($nickMap) && isset($nickMap[$login]) && is_array($nickMap[$login])) {
                        $row = $nickMap[$login];

                        if ((!isset($out[$envSafe][$login]['NickName']) || (string)$out[$envSafe][$login]['NickName'] === '')
                            && isset($row['NickName']) && (string)$row['NickName'] !== '') {
                            $out[$envSafe][$login]['NickName'] = (string)$row['NickName'];
                        }
                        if ((!isset($out[$envSafe][$login]['NickNameWithColor']) || (string)$out[$envSafe][$login]['NickNameWithColor'] === '')
                            && isset($row['NickNameWithColor']) && (string)$row['NickNameWithColor'] !== '') {
                            $out[$envSafe][$login]['NickNameWithColor'] = (string)$row['NickNameWithColor'];
                        }
                    }
                }
            }
        }

        return $out;
    }

    private static function pointsForRank($pointsDistribution, $index0, $rank1) {
        $points = 0;
        // supports 0-based distribution (0=>rank1) and also 1-based if ever used
        if (isset($pointsDistribution[$index0])) {
            $points = (int)$pointsDistribution[$index0];
        } elseif (isset($pointsDistribution[$rank1])) {
            $points = (int)$pointsDistribution[$rank1];
        }

        return $points;
    }

    private static function extractEnvFromBestRacesFilename($filePath) {
        // expected: fastlog/um/bestScores.<Env>.<Uid>.txt
        $base = basename($filePath);
        $parts = explode('.', $base);
        // [0]=bestScores, [1]=Env, [2]=Uid, [3]=txt
        if (count($parts) >= 4 && $parts[0] === 'bestScores') {
            return $parts[1];
        }
        return '';
    }

    private static function pickMapIdFromParsedBestRaces($all, $wantedMapId) {
        if (!is_array($all) || count($all) < 1) return '';

        if ($wantedMapId !== '' && isset($all[$wantedMapId])) {
            return (string)$wantedMapId;
        }

        // If only one section exists, use it.
        $keys = array_keys($all);
        if (count($keys) === 1) {
            return (string)$keys[0];
        }

        // Fallback: take the first section (deterministic-ish if file order is stable).
        return isset($keys[0]) ? (string)$keys[0] : '';
    }

}

function bestScoresCompare($a, $b) {
    $aCheck = isset($a['Check']) ? (int)$a['Check'] : 0;
    $bCheck = isset($b['Check']) ? (int)$b['Check'] : 0;
    if ($aCheck > $bCheck) return -1;
    if ($aCheck < $bCheck) return 1;

    $aTime = isset($a['TimeMs']) ? (int)$a['TimeMs'] : 0;
    $bTime = isset($b['TimeMs']) ? (int)$b['TimeMs'] : 0;
    if ($aTime < $bTime) return -1;
    if ($aTime > $bTime) return 1;

    $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
    $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
    return strcmp($aLogin, $bLogin);
}