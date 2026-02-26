<?php

class BestRaces {
    const DIR_UM = 'fastlog/um';

    const FILE_PREFIX_BEST_RACES = 'bestRaces';
    const FILE_PREFIX_BEST_LAPS = 'bestLaps';

    // Stored as first CSV column in map meta row.
    // Full row format: ### MAP,<Environment>,<MapId>,<MapName>,<Author>
    const MAP_MARKER = '### MAP';

    const ENV_UNKNOWN = 'Unknown';

    /**
     * Update UM best race progress file (per environment), only if improved:
     * - Check DESC (more checkpoints is better)
     * - TimeMs ASC for equal Check (lower is better)
     *
     * File: fastlog/um/bestRaces.<env>.<uid>.txt
     */
    public static function updateBestRacesFile($finishedPlayers, $challengeInfo) {
        self::updateBestFile(self::FILE_PREFIX_BEST_RACES, $finishedPlayers, $challengeInfo);
    }

    /**
     * Update UM best laps file (per environment), only if improved:
     * - BestLapMs ASC (lower is better)
     *
     * File: fastlog/um/bestLaps.<env>.<uid>.txt
     */
    public static function updateBestLapsFile($finishedPlayers, $challengeInfo) {
        self::updateBestFile(self::FILE_PREFIX_BEST_LAPS, $finishedPlayers, $challengeInfo);
    }

    /**
     * Shared update pipeline for "best races" and "best laps".
     */
    private static function updateBestFile($kindPrefix, $finishedPlayers, $challengeInfo) {
        if (!is_array($finishedPlayers) || count($finishedPlayers) < 1) {
            return;
        }

        $env = isset($challengeInfo['Environnement']) ? (string)$challengeInfo['Environnement'] : self::ENV_UNKNOWN;
        $envSafe = self::sanitizeToken($env, self::ENV_UNKNOWN);

        $uidSafe = self::sanitizeToken(isset($challengeInfo['UId']) ? $challengeInfo['UId'] : '', '');
        if ($uidSafe === '') {
            return;
        }

        $filePath = self::DIR_UM . '/' . $kindPrefix . '.' . $envSafe . '.' . $uidSafe . '.txt';
        FastFile::ensureFile($filePath);

        $mapId = (string)getChallengeID($challengeInfo);
        if ($mapId === '') {
            return;
        }

        $mapName = isset($challengeInfo['Name']) ? stripColors($challengeInfo['Name']) : '';
        $mapAuthor = isset($challengeInfo['Author']) ? stripColors($challengeInfo['Author']) : '';

        $meta = array(
            'Environment' => $env,
            'MapId' => $mapId,
            'MapName' => $mapName,
            'Author' => $mapAuthor,
        );

        $spec = self::specForKind($kindPrefix);
        $data = CsvFile::parse($filePath, $spec);

        $data = self::ensureMapSection($data, $mapId, $meta);

        $now = self::now();
        foreach ($finishedPlayers as $p) {
            if (!is_array($p)) {
                continue;
            }

            $row = call_user_func($spec['buildRowFromFinishedPlayer'], $p, $now);
            if ($row === null) {
                continue;
            }

            $login = isset($row['Login']) ? (string)$row['Login'] : '';
            if ($login === '') {
                continue;
            }

            $old = isset($data[$mapId]['players'][$login]) ? $data[$mapId]['players'][$login] : null;
            if ($old === null || call_user_func($spec['isImproved'], $row, $old)) {
                $data[$mapId]['players'][$login] = $row;
            }
        }

        foreach ($data as $mId => $section) {
            if (!isset($section['players']) || !is_array($section['players'])) {
                continue;
            }
            $data[$mId]['players'] = self::sortAndRekeyByLogin($section['players'], $spec['compareRows']);
        }

        CsvFile::writeAtomic($filePath, $data, $spec);
    }

    /**
     * Per-kind behavior spec (keeps update pipeline generic).
     */
    private static function specForKind($kindPrefix) {
        if ($kindPrefix === self::FILE_PREFIX_BEST_RACES) {
            return array(
                'title' => 'UM Best Scores',
                'sortComment' => 'Sort: Check DESC, then Time ASC',
                'header' => array('Login', 'Check', 'Lap', 'TimeMs', 'Time', 'BestLapMs', 'BestLap', 'UpdatedAt'),
                'compareRows' => array('BestRaces', 'compareBestRacesRows'),
                'buildRowFromFinishedPlayer' => array('BestRaces', 'buildBestRaceRow'),
                'isImproved' => array('BestRaces', 'isBestRaceImproved'),
                'serializePlayerRow' => array('BestRaces', 'serializeBestRaceRow'),
                'parsePlayerRow' => array('BestRaces', 'parseBestRaceRow'),
            );
        }

        return array(
            'title' => 'UM Best Laps',
            'sortComment' => 'Sort: BestLapMs ASC',
            'header' => array('Login', 'BestLapMs', 'BestLap', 'UpdatedAt'),
            'compareRows' => array('BestRaces', 'compareBestLapsRows'),
            'buildRowFromFinishedPlayer' => array('BestRaces', 'buildBestLapRow'),
            'isImproved' => array('BestRaces', 'isBestLapImproved'),
            'serializePlayerRow' => array('BestRaces', 'serializeBestLapRow'),
            'parsePlayerRow' => array('BestRaces', 'parseBestLapRow'),
        );
    }

    private static function sanitizeToken($s, $default) {
        $s = preg_replace('/[^A-Za-z0-9_-]+/', '', (string)$s);
        return ($s !== '') ? $s : $default;
    }

    private static function now() {
        return date('Y-m-d H:i:s');
    }

    private static function ensureMapSection($data, $mapId, $meta) {
        if (!isset($data[$mapId]) || !is_array($data[$mapId])) {
            $data[$mapId] = array(
                'meta' => array(),
                'players' => array(),
            );
        }

        if (!isset($data[$mapId]['meta']) || !is_array($data[$mapId]['meta'])) {
            $data[$mapId]['meta'] = array();
        }

        foreach ($meta as $k => $v) {
            $data[$mapId]['meta'][$k] = $v;
        }

        if (!isset($data[$mapId]['players']) || !is_array($data[$mapId]['players'])) {
            $data[$mapId]['players'] = array();
        }

        return $data;
    }

    private static function sortAndRekeyByLogin($players, $compareCallable) {
        $rows = array_values($players);
        usort($rows, $compareCallable);

        $out = array();
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }
            $login = isset($r['Login']) ? (string)$r['Login'] : '';
            if ($login === '') {
                continue;
            }
            $out[$login] = $r;
        }

        return $out;
    }

    // ---------------------------------------------------------------------
    // Best races: build / compare / improve / (de)serialize
    // ---------------------------------------------------------------------

    public static function buildBestRaceRow($p, $now) {
        $login = isset($p['Login']) ? stripColors($p['Login']) : '';
        if ($login === '') {
            return null;
        }

        $lap = isset($p['Lap']) ? (int)$p['Lap'] : 0;
        $check = isset($p['Check']) ? (int)$p['Check'] : 0;
        $timeMs = isset($p['Time']) ? (int)$p['Time'] : 0;
        $bestLapMs = isset($p['BestLap']) ? (int)$p['BestLap'] : 0;

        if ($timeMs <= 0) {
            return null;
        }

        return array(
            'Login' => $login,
            'Lap' => $lap,
            'Check' => $check,
            'TimeMs' => $timeMs,
            'BestLapMs' => $bestLapMs,
            'UpdatedAt' => (string)$now,
        );
    }

    public static function isBestRaceImproved($newRow, $oldRow) {
        $check = isset($newRow['Check']) ? (int)$newRow['Check'] : 0;
        $timeMs = isset($newRow['TimeMs']) ? (int)$newRow['TimeMs'] : 0;

        $oldCheck = isset($oldRow['Check']) ? (int)$oldRow['Check'] : 0;
        $oldTimeMs = isset($oldRow['TimeMs']) ? (int)$oldRow['TimeMs'] : 0;

        if ($check > $oldCheck) {
            return true;
        }
        if ($check < $oldCheck) {
            return false;
        }

        // Same check: lower time wins; missing/invalid old time => accept new.
        if ($oldTimeMs <= 0) {
            return true;
        }

        return ($timeMs > 0 && $timeMs < $oldTimeMs);
    }

    public static function compareBestRacesRows($a, $b) {
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

    public static function serializeBestRaceRow($row) {
        $login = isset($row['Login']) ? (string)$row['Login'] : '';
        $check = isset($row['Check']) ? (int)$row['Check'] : 0;
        $lap = isset($row['Lap']) ? (int)$row['Lap'] : 0;
        $timeMs = isset($row['TimeMs']) ? (int)$row['TimeMs'] : 0;
        $bestLapMs = isset($row['BestLapMs']) ? (int)$row['BestLapMs'] : 0;
        $updatedAt = isset($row['UpdatedAt']) ? (string)$row['UpdatedAt'] : '';

        $timeStr = $timeMs > 0 ? MwTimeToString($timeMs) : '';
        $bestLapStr = $bestLapMs > 0 ? MwTimeToString($bestLapMs) : '';

        return array($login, $check, $lap, $timeMs, $timeStr, $bestLapMs, $bestLapStr, $updatedAt);
    }

    public static function parseBestRaceRow($cols) {
        if (!is_array($cols) || count($cols) < 4) {
            return null;
        }

        $login = isset($cols[0]) ? (string)$cols[0] : '';
        if ($login === '') {
            return null;
        }

        return array(
            'Login' => $login,
            'Check' => isset($cols[1]) ? (int)$cols[1] : 0,
            'Lap' => isset($cols[2]) ? (int)$cols[2] : 0,
            'TimeMs' => isset($cols[3]) ? (int)$cols[3] : 0,
            'BestLapMs' => isset($cols[5]) ? (int)$cols[5] : 0,
            'UpdatedAt' => isset($cols[7]) ? (string)$cols[7] : '',
        );
    }

    // ---------------------------------------------------------------------
    // Best laps: build / compare / improve / (de)serialize
    // ---------------------------------------------------------------------

    public static function buildBestLapRow($p, $now) {
        $login = isset($p['Login']) ? stripColors($p['Login']) : '';
        if ($login === '') {
            return null;
        }

        $bestLapMs = isset($p['BestLap']) ? (int)$p['BestLap'] : 0;
        if ($bestLapMs <= 0) {
            return null;
        }

        return array(
            'Login' => $login,
            'BestLapMs' => $bestLapMs,
            'UpdatedAt' => (string)$now,
        );
    }

    public static function isBestLapImproved($newRow, $oldRow) {
        $bestLapMs = isset($newRow['BestLapMs']) ? (int)$newRow['BestLapMs'] : 0;
        $oldBestLapMs = isset($oldRow['BestLapMs']) ? (int)$oldRow['BestLapMs'] : 0;

        if ($bestLapMs <= 0) return false;
        if ($oldBestLapMs <= 0) return true;

        return ($bestLapMs < $oldBestLapMs);
    }

    public static function compareBestLapsRows($a, $b) {
        $aMs = isset($a['BestLapMs']) ? (int)$a['BestLapMs'] : 0;
        $bMs = isset($b['BestLapMs']) ? (int)$b['BestLapMs'] : 0;

        // Treat <=0 as "worst" so valid laps come first.
        $aBad = ($aMs <= 0);
        $bBad = ($bMs <= 0);
        if ($aBad && !$bBad) return 1;
        if (!$aBad && $bBad) return -1;

        if ($aMs < $bMs) return -1;
        if ($aMs > $bMs) return 1;

        $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
        $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
        return strcmp($aLogin, $bLogin);
    }

    public static function serializeBestLapRow($row) {
        $login = isset($row['Login']) ? (string)$row['Login'] : '';
        $bestLapMs = isset($row['BestLapMs']) ? (int)$row['BestLapMs'] : 0;
        $updatedAt = isset($row['UpdatedAt']) ? (string)$row['UpdatedAt'] : '';

        $bestLapStr = $bestLapMs > 0 ? MwTimeToString($bestLapMs) : '';
        return array($login, $bestLapMs, $bestLapStr, $updatedAt);
    }

    public static function parseBestLapRow($cols) {
        if (!is_array($cols) || count($cols) < 2) {
            return null;
        }

        $login = isset($cols[0]) ? (string)$cols[0] : '';
        if ($login === '') {
            return null;
        }

        return array(
            'Login' => $login,
            'BestLapMs' => isset($cols[1]) ? (int)$cols[1] : 0,
            'UpdatedAt' => isset($cols[3]) ? (string)$cols[3] : '',
        );
    }

    // ---------------------------------------------------------------------
    // Compatibility wrappers: keep old private parse/write names used elsewhere
    // ---------------------------------------------------------------------

    /**
     * Legacy wrapper (used by qualification aggregation).
     */
    private static function parseBestRacesFile($filePath) {
        $spec = self::specForKind(self::FILE_PREFIX_BEST_RACES);
        return CsvFile::parse($filePath, $spec);
    }

    /**
     * Legacy wrapper.
     */
    private static function writeBestRacesFile($filePath, $data) {
        $spec = self::specForKind(self::FILE_PREFIX_BEST_RACES);
        CsvFile::writeAtomic($filePath, $data, $spec);
    }

    /**
     * Legacy wrapper.
     */
    private static function parseBestLapsFile($filePath) {
        $spec = self::specForKind(self::FILE_PREFIX_BEST_LAPS);
        return CsvFile::parse($filePath, $spec);
    }

    /**
     * Legacy wrapper.
     */
    private static function writeBestLapsFile($filePath, $data) {
        $spec = self::specForKind(self::FILE_PREFIX_BEST_LAPS);
        CsvFile::writeAtomic($filePath, $data, $spec);
    }

    // ---------------------------------------------------------------------
    // Qualification aggregation (kept from your original implementation)
    // ---------------------------------------------------------------------

    public static function buildQualificationRankingsAllMaps($qualiBestRacesConfig, $nickMap) {
        $byEnvLogin = self::buildQualificationScoresAllMaps($qualiBestRacesConfig, $nickMap);
        return self::normalizeEnvLoginMapToRankedLists($byEnvLogin);
    }

    /**
     * New: rankings for best laps (same aggregation logic, different source files).
     */
    public static function buildQualificationRankingsAllMapsBestLaps($qualiBestLapsConfig, $nickMap) {
        $byEnvLogin = self::buildQualificationScoresAllMapsBestLaps($qualiBestLapsConfig, $nickMap);
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

        // Tie-breaker: more maps played first
        $aMp = isset($a['MapsPlayed']) ? (int)$a['MapsPlayed'] : 0;
        $bMp = isset($b['MapsPlayed']) ? (int)$b['MapsPlayed'] : 0;
        if ($aMp > $bMp) return -1;
        if ($aMp < $bMp) return 1;

        $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
        $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
        return strcmp($aLogin, $bLogin);
    }

    /**
     * Build aggregated qualification scores across ALL configured maps, from bestRaces files.
     */
    public static function buildQualificationScoresAllMaps(UMConfigEntry $qualiBestRacesConfig, $nickMap) {
        return self::buildQualificationScoresAllMapsForKind(self::FILE_PREFIX_BEST_RACES, $qualiBestRacesConfig, $nickMap);
    }

    /**
     * New: same as buildQualificationScoresAllMaps(), but reads from bestLaps files.
     */
    public static function buildQualificationScoresAllMapsBestLaps(UMConfigEntry $qualiBestLapsConfig, $nickMap) {
        return self::buildQualificationScoresAllMapsForKind(self::FILE_PREFIX_BEST_LAPS, $qualiBestLapsConfig, $nickMap);
    }
    /**
     * Shared implementation for qualification aggregation.
     *
     * $kindPrefix: one of FILE_PREFIX_BEST_RACES / FILE_PREFIX_BEST_LAPS
     */
    private static function buildQualificationScoresAllMapsForKind($kindPrefix, UMConfigEntry $config, $nickMap) {
        $maps = $config->maps;
        $pointsDistribution = $config->pointsDistribution;

        $out = array(); // env => login => row

        foreach ($maps as $m) {
            $uidSafe = self::safeUidFromMapConfig($m);
            if ($uidSafe === '') continue;

            $wantedMapId = self::wantedMapIdFromMapConfig($m);

            $files = self::listBestFilesForUid($kindPrefix, $uidSafe);
            if (!is_array($files) || count($files) < 1) continue;

            foreach ($files as $filePath) {
                if (!is_string($filePath) || $filePath === '') continue;
                if (!file_exists($filePath)) continue;

                $envSafe = self::extractEnvFromBestFilename($kindPrefix, $filePath);
                if ($envSafe === '') $envSafe = self::ENV_UNKNOWN;

                $all = self::parseBestFileForKind($kindPrefix, $filePath);
                if (!is_array($all) || count($all) < 1) continue;

                $mapIdToUse = self::pickMapIdFromParsedBestFile($all, $wantedMapId);
                if ($mapIdToUse === '') continue;

                $ranking = self::extractRankingFromParsedBestFile($all, $mapIdToUse);
                if (!is_array($ranking) || count($ranking) < 1) continue;

                $count = count($ranking);
                for ($i = 0; $i < $count; $i++) {
                    if (!isset($ranking[$i]) || !is_array($ranking[$i])) continue;

                    $login = isset($ranking[$i]['Login']) ? stripColors($ranking[$i]['Login']) : '';
                    if ($login === '') continue;

                    $rank = $i + 1;
                    $points = self::pointsForRank($pointsDistribution, $i, $rank);

                    self::applyQualificationPoints($out, $envSafe, $login, $uidSafe, $points);
                    self::mergeNicknamesIfMissing($out, $envSafe, $login, $nickMap);

                    // NEW: capture best time fields into the aggregated row
                    self::mergeBestTimesIntoQualificationRow($out[$envSafe][$login], $kindPrefix, $ranking[$i]);
                }
            }
        }

        return $out;
    }

    /**
     * Enriches a qualification aggregate row with best race / lap times.
     *
     * - bestRaces rows use TimeMs
     * - bestLaps  rows use BestLapMs
     *
     * Keeps the minimum positive ms encountered.
     */
    private static function mergeBestTimesIntoQualificationRow(&$outRow, $kindPrefix, $sourceRow) {
        if (!is_array($outRow) || !is_array($sourceRow)) return;

        if ($kindPrefix === self::FILE_PREFIX_BEST_RACES) {
            $ms = isset($sourceRow['TimeMs']) ? (int)$sourceRow['TimeMs'] : 0;
            if ($ms > 0) {
                $cur = isset($outRow['BestRaceTimeMs']) ? (int)$outRow['BestRaceTimeMs'] : 0;
                if ($cur <= 0 || $ms < $cur) {
                    $outRow['BestRaceTimeMs'] = $ms;
                    $outRow['BestRaceTime'] = MwTimeToString($ms);
                }
            }
            return;
        }

        if ($kindPrefix === self::FILE_PREFIX_BEST_LAPS) {
            $ms = isset($sourceRow['BestLapMs']) ? (int)$sourceRow['BestLapMs'] : 0;
            if ($ms > 0) {
                $cur = isset($outRow['BestLapTimeMs']) ? (int)$outRow['BestLapTimeMs'] : 0;
                if ($cur <= 0 || $ms < $cur) {
                    $outRow['BestLapTimeMs'] = $ms;
                    $outRow['BestLapTime'] = MwTimeToString($ms);
                }
            }
            return;
        }
    }

    private static function safeUidFromMapConfig($m) {
        $uid = isset($m->id) ? (string)$m->id : '';
        $uidSafe = preg_replace('/[^A-Za-z0-9_-]/', '', $uid);
        return (string)$uidSafe;
    }

    private static function wantedMapIdFromMapConfig($m) {
        if (isset($m->mapId)) return (string)$m->mapId;
        if (isset($m->MapId)) return (string)$m->MapId;
        return '';
    }

    private static function listBestFilesForUid($kindPrefix, $uidSafe) {
        $pattern = self::DIR_UM . '/' . $kindPrefix . '.*.' . $uidSafe . '.txt';
        $files = glob($pattern);
        if ($files === false || !is_array($files)) return array();
        return $files;
    }

    private static function parseBestFileForKind($kindPrefix, $filePath) {
        if ($kindPrefix === self::FILE_PREFIX_BEST_LAPS) {
            return self::parseBestLapsFile($filePath);
        }
        return self::parseBestRacesFile($filePath);
    }

    private static function extractRankingFromParsedBestFile($all, $mapIdToUse) {
        if (!isset($all[$mapIdToUse]['players']) || !is_array($all[$mapIdToUse]['players'])) return array();
        return array_values($all[$mapIdToUse]['players']);
    }

    private static function applyQualificationPoints(&$out, $envSafe, $login, $uidSafe, $points) {
        if (!isset($out[$envSafe])) $out[$envSafe] = array();

        if (!isset($out[$envSafe][$login]) || !is_array($out[$envSafe][$login])) {
            $out[$envSafe][$login] = array(
                'Login' => $login,
                'Score' => 0,
                'MapsPlayed' => 0,
                'PerMap' => array(), // uidSafe => points
            );
        }

        $out[$envSafe][$login]['Score'] += (int)$points;
        $out[$envSafe][$login]['MapsPlayed'] += 1;
        $out[$envSafe][$login]['PerMap'][$uidSafe] = (int)$points;
    }

    private static function mergeNicknamesIfMissing(&$out, $envSafe, $login, $nickMap) {
        if (!is_array($nickMap) || !isset($nickMap[$login]) || !is_array($nickMap[$login])) {
            return;
        }

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

    /**
     * New generic env extractor:
     * expected: fastlog/um/<kind>.<Env>.<Uid>.txt
     */
    private static function extractEnvFromBestFilename($kindPrefix, $filePath) {
        $base = basename($filePath);
        $parts = explode('.', $base);
        if (count($parts) >= 4 && $parts[0] === $kindPrefix) {
            return (string)$parts[1];
        }
        return '';
    }

    private static function pickMapIdFromParsedBestRaces($all, $wantedMapId) {
        if (!is_array($all) || count($all) < 1) return '';

        if ($wantedMapId !== '' && isset($all[$wantedMapId])) {
            return (string)$wantedMapId;
        }

        $keys = array_keys($all);
        if (count($keys) === 1) {
            return (string)$keys[0];
        }

        return isset($keys[0]) ? (string)$keys[0] : '';
    }

    /**
     * New generic picker (works for bestRaces + bestLaps since both are mapId => section arrays).
     */
    private static function pickMapIdFromParsedBestFile($all, $wantedMapId) {
        return self::pickMapIdFromParsedBestRaces($all, $wantedMapId);
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


}

/**
 * Backward-compatible global comparators.
 * Keep these if any legacy code calls usort($rows, 'bestRacesCompare') / 'bestLapsCompare'.
 */
function bestRacesCompare($a, $b) { return BestRaces::compareBestRacesRows($a, $b); }
function bestLapsCompare($a, $b)  { return BestRaces::compareBestLapsRows($a, $b); }