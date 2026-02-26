<?php
class QualificationRankingService {
    static function mergeQualificationScoresByEnv($racesRanking, $lapsRanking) {
        $acc = array(); // env => login => row (with merged PerMap)
        $ingest = function($src, $kind) use (&$acc) {
            if (!is_array($src)) return;
            foreach ($src as $env => $list) {
                if (!isset($acc[$env])) $acc[$env] = array();
                if (!is_array($list)) continue;
                foreach ($list as $row) {
                    if (!is_array($row)) continue;
                    $login = isset($row['Login']) ? (string)$row['Login'] : '';
                    if ($login === '') continue;
                    if (!isset($acc[$env][$login])) {
                        $acc[$env][$login] = array(
                            'Login' => $login,
                            'Score' => 0,              // recomputed at finalize
                            'MapsPlayed' => 0,         // recomputed as unique map count
                            'PerMap' => array(),       // uid => totalPointsFromBoth
                            'BestRaceScore' => 0,      // recomputed at finalize
                            'BestLapScore' => 0,       // recomputed at finalize
                            '_perMapBestRace' => array(), // uid => points (races only)
                            '_perMapBestLap' => array(),  // uid => points (laps only)

                            // Best times (merged from both sources)
                            'BestRaceTime' => '',
                            'BestRaceTimeMs' => 0,
                            'BestLapTime' => '',
                            'BestLapTimeMs' => 0,
                        );
                    }

                    // Merge PerMap by summing points per uid (overall + per-kind)
                    if (isset($row['PerMap']) && is_array($row['PerMap'])) {
                        foreach ($row['PerMap'] as $uid => $pts) {
                            $uid = (string)$uid;
                            $pts = (int)$pts;
                            if (!isset($acc[$env][$login]['PerMap'][$uid])) {
                                $acc[$env][$login]['PerMap'][$uid] = 0;
                            }
                            $acc[$env][$login]['PerMap'][$uid] += $pts;
                            if ($kind === 'race') {
                                if (!isset($acc[$env][$login]['_perMapBestRace'][$uid])) {
                                    $acc[$env][$login]['_perMapBestRace'][$uid] = 0;
                                }
                                $acc[$env][$login]['_perMapBestRace'][$uid] += $pts;
                            } else { // 'lap'
                                if (!isset($acc[$env][$login]['_perMapBestLap'][$uid])) {
                                    $acc[$env][$login]['_perMapBestLap'][$uid] = 0;
                                }
                                $acc[$env][$login]['_perMapBestLap'][$uid] += $pts;
                            }
                        }
                    }

                    // Merge best times (pick smallest positive ms, keep matching formatted string if provided)
                    if ($kind === 'race') {
                        $incomingMs = isset($row['BestRaceTimeMs']) ? (int)$row['BestRaceTimeMs'] : 0;
                        if ($incomingMs > 0) {
                            $currentMs = isset($acc[$env][$login]['BestRaceTimeMs']) ? (int)$acc[$env][$login]['BestRaceTimeMs'] : 0;
                            if ($currentMs <= 0 || $incomingMs < $currentMs) {
                                $acc[$env][$login]['BestRaceTimeMs'] = $incomingMs;
                                if (isset($row['BestRaceTime']) && (string)$row['BestRaceTime'] !== '') {
                                    $acc[$env][$login]['BestRaceTime'] = (string)$row['BestRaceTime'];
                                }
                            }
                        } elseif (isset($row['BestRaceTime']) && (string)$row['BestRaceTime'] !== '') {
                            if (!isset($acc[$env][$login]['BestRaceTime']) || (string)$acc[$env][$login]['BestRaceTime'] === '') {
                                $acc[$env][$login]['BestRaceTime'] = (string)$row['BestRaceTime'];
                            }
                        }
                    } else { // 'lap'
                        $incomingMs = isset($row['BestLapTimeMs']) ? (int)$row['BestLapTimeMs'] : 0;
                        if ($incomingMs > 0) {
                            $currentMs = isset($acc[$env][$login]['BestLapTimeMs']) ? (int)$acc[$env][$login]['BestLapTimeMs'] : 0;
                            if ($currentMs <= 0 || $incomingMs < $currentMs) {
                                $acc[$env][$login]['BestLapTimeMs'] = $incomingMs;
                                if (isset($row['BestLapTime']) && (string)$row['BestLapTime'] !== '') {
                                    $acc[$env][$login]['BestLapTime'] = (string)$row['BestLapTime'];
                                }
                            }
                        } elseif (isset($row['BestLapTime']) && (string)$row['BestLapTime'] !== '') {
                            if (!isset($acc[$env][$login]['BestLapTime']) || (string)$acc[$env][$login]['BestLapTime'] === '') {
                                $acc[$env][$login]['BestLapTime'] = (string)$row['BestLapTime'];
                            }
                        }
                    }

                    // Keep nicknames if present (either source can provide them)
                    if (isset($row['NickName']) && (string)$row['NickName'] !== '') {
                        $acc[$env][$login]['NickName'] = (string)$row['NickName'];
                    }
                    if (isset($row['NickNameWithColor']) && (string)$row['NickNameWithColor'] !== '') {
                        $acc[$env][$login]['NickNameWithColor'] = (string)$row['NickNameWithColor'];
                    }
                }
            }
        };
        $ingest($racesRanking, 'race');
        $ingest($lapsRanking, 'lap');
        // Finalize: recompute Score + MapsPlayed, then rank per env
        $final = array();
        foreach ($acc as $env => $loginMap) {
            $list = array();
            foreach ($loginMap as $login => $row) {
                // Unique maps played = number of uid keys with any (non-zero) points
                $mapsPlayed = 0;
                $score = 0;
                if (isset($row['PerMap']) && is_array($row['PerMap'])) {
                    foreach ($row['PerMap'] as $uid => $pts) {
                        $pts = (int)$pts;
                        if ($pts !== 0) {
                            $mapsPlayed++;
                            $score += $pts;
                        }
                    }
                }
                $scoreBestRace = 0;
                $mapsPlayedBestRace = 0;
                if (isset($row['_perMapBestRace']) && is_array($row['_perMapBestRace'])) {
                    foreach ($row['_perMapBestRace'] as $uid => $pts) {
                        $pts = (int)$pts;
                        if ($pts !== 0) {
                            $mapsPlayedBestRace++;
                            $scoreBestRace += $pts;
                        }
                    }
                }
                $scoreBestLap = 0;
                $mapsPlayedBestLap = 0;
                if (isset($row['_perMapBestLap']) && is_array($row['_perMapBestLap'])) {
                    foreach ($row['_perMapBestLap'] as $uid => $pts) {
                        $pts = (int)$pts;
                        if ($pts !== 0) {
                            $mapsPlayedBestLap++;
                            $scoreBestLap += $pts;
                        }
                    }
                }
                $row['MapsPlayed'] = $mapsPlayed;
                $row['Score'] = $score;
                $row['BestRaceScore'] = $scoreBestRace;
                $row['BestLapScore'] = $scoreBestLap;
                // internal helpers for tie-breakers in sub-ranks
                $row['_mapsPlayedBestRace'] = $mapsPlayedBestRace;
                $row['_mapsPlayedBestLap'] = $mapsPlayedBestLap;
                $list[] = $row;
            }

            // --- compute rankBestRace / rankBestLap (within this env) ---
            $rankBestRaceByLogin = buildRankByLogin($list, 'BestRaceScore', '_mapsPlayedBestRace');
            $rankBestLapByLogin  = buildRankByLogin($list, 'BestLapScore',  '_mapsPlayedBestLap');

            // --- overall ranking stays as-is (Score / MapsPlayed / Login) ---
            usort($list, function($a, $b) {
                $aScore = isset($a['Score']) ? (int)$a['Score'] : 0;
                $bScore = isset($b['Score']) ? (int)$b['Score'] : 0;
                if ($aScore > $bScore) return -1;
                if ($aScore < $bScore) return 1;
                $aMp = isset($a['MapsPlayed']) ? (int)$a['MapsPlayed'] : 0;
                $bMp = isset($b['MapsPlayed']) ? (int)$b['MapsPlayed'] : 0;
                if ($aMp > $bMp) return -1;
                if ($aMp < $bMp) return 1;
                $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
                $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
                return strcmp($aLogin, $bLogin);
            });
            $rank = 1;
            for ($i = 0; $i < count($list); $i++) {
                $login = isset($list[$i]['Login']) ? (string)$list[$i]['Login'] : '';
                $list[$i]['Rank'] = $rank++;
                $list[$i]['rankBestRace'] = ($login !== '' && isset($rankBestRaceByLogin[$login])) ? (int)$rankBestRaceByLogin[$login] : 0;
                $list[$i]['rankBestLap'] = ($login !== '' && isset($rankBestLapByLogin[$login])) ? (int)$rankBestLapByLogin[$login] : 0;
                // internal fields not needed in output
                unset($list[$i]['_perMapBestRace']);
                unset($list[$i]['_perMapBestLap']);
                unset($list[$i]['_mapsPlayedBestRace']);
                unset($list[$i]['_mapsPlayedBestLap']);
            }
            $final[$env] = $list;
        }
        return $final;
    }

    /**
     * Build a global leaderboard across all environments (one map per env).
     *
     * Input: $rankingsByEnv = env => list(rows) where each row contains:
     * - Login
     * - PerMap (uidSafe => points)  (only 1 uid per env in your setup)
     * - PerMapBestRace (uidSafe => points)  (added by mergeQualificationScoresByEnv)
     * - PerMapBestLap  (uidSafe => points)  (added by mergeQualificationScoresByEnv)
     * - BestRaceTime/BestRaceTimeMs (env-level)
     * - BestLapTime/BestLapTimeMs   (env-level)
     *
     * Output: list(rows) sorted by Score desc, MapsPlayed desc, TotalTimeMs asc (if both >0), Login asc.
     * PerMap shape: PerMap[Env] => details (no uid key level).
     */
    /**
     * Global leaderboard across all envs (one map per env).
     *
     * Input: env => list of rows, each row like:
     * - Login, Score, MapsPlayed, PerMap[<MapId>] => points
     * - BestRaceScore, BestLapScore
     * - BestRaceTime/BestRaceTimeMs, BestLapTime/BestLapTimeMs
     * - rankBestRace, rankBestLap
     *
     * Output: list of players with PerMap[Env] flattened (no MapId key level).
     * MapsPlayed uses A: counts env entries where (BestRaceScore+BestLapScore) != 0.
     */
    static function buildQualificationLeaderboardAllEnvs($rankingsByEnv) {
        if (!is_array($rankingsByEnv)) return array();

        $acc = array(); // login => aggregated row

        foreach ($rankingsByEnv as $env => $rows) {
            if (!is_array($rows)) continue;

            $envKey = (string)$env;
            if ($envKey === '') $envKey = 'unknown';

            $count = count($rows);
            for ($i = 0; $i < $count; $i++) {
                $row = $rows[$i];
                if (!is_array($row)) continue;

                $login = isset($row['Login']) ? (string)$row['Login'] : '';
                if ($login === '') continue;

                if (!isset($acc[$login])) {
                    $acc[$login] = array(
                        'Login' => $login,
                        'Score' => 0,        // computed as sum of env totals
                        'TotalTime' => '',   // computed at finalize
                        'TotalTimeMs' => 0,  // computed at finalize
                        'MapsPlayed' => 0,   // computed at finalize (A)
                        'PerMap' => array(), // Env => details
                    );
                }

                // Keep nicknames if present
                if (isset($row['NickName']) && (string)$row['NickName'] !== '') {
                    $acc[$login]['NickName'] = (string)$row['NickName'];
                }
                if (isset($row['NickNameWithColor']) && (string)$row['NickNameWithColor'] !== '') {
                    $acc[$login]['NickNameWithColor'] = (string)$row['NickNameWithColor'];
                }

                // Single map id for this env: first key of PerMap
                $mapId = '';
                if (isset($row['PerMap']) && is_array($row['PerMap'])) {
                    foreach ($row['PerMap'] as $uid => $pts) {
                        $uid = (string)$uid;
                        if ($uid !== '') { $mapId = $uid; break; }
                    }
                }

                if (!isset($acc[$login]['PerMap'][$envKey])) {
                    $acc[$login]['PerMap'][$envKey] = array(
                        'MapId' => $mapId,

                        'BestRaceScore' => 0,
                        'BestRaceRank' => 0,

                        'BestLapScore' => 0,
                        'BestLapRank' => 0,

                        'BestRaceTime' => '',
                        'BestRaceTimeMs' => 0,

                        'BestLapTime' => '',
                        'BestLapTimeMs' => 0,
                    );
                } else {
                    if ($acc[$login]['PerMap'][$envKey]['MapId'] === '' && $mapId !== '') {
                        $acc[$login]['PerMap'][$envKey]['MapId'] = $mapId;
                    }
                }

                // Pull scores directly from the env-row (these DO exist in your data)
                $bestRaceScore = isset($row['BestRaceScore']) ? (int)$row['BestRaceScore'] : 0;
                $bestLapScore  = isset($row['BestLapScore']) ? (int)$row['BestLapScore'] : 0;

                $acc[$login]['PerMap'][$envKey]['BestRaceScore'] = $bestRaceScore;
                $acc[$login]['PerMap'][$envKey]['BestLapScore']  = $bestLapScore;

                // Ranks already computed per env in the env ranking
                $acc[$login]['PerMap'][$envKey]['BestRaceRank'] = isset($row['rankBestRace']) ? (int)$row['rankBestRace'] : 0;
                $acc[$login]['PerMap'][$envKey]['BestLapRank']  = isset($row['rankBestLap']) ? (int)$row['rankBestLap'] : 0;

                // Times: also directly available per env
                $acc[$login]['PerMap'][$envKey]['BestRaceTimeMs'] = isset($row['BestRaceTimeMs']) ? (int)$row['BestRaceTimeMs'] : 0;
                $acc[$login]['PerMap'][$envKey]['BestRaceTime']   = isset($row['BestRaceTime']) ? (string)$row['BestRaceTime'] : '';

                $acc[$login]['PerMap'][$envKey]['BestLapTimeMs'] = isset($row['BestLapTimeMs']) ? (int)$row['BestLapTimeMs'] : 0;
                $acc[$login]['PerMap'][$envKey]['BestLapTime']   = isset($row['BestLapTime']) ? (string)$row['BestLapTime'] : '';
            }
        }

        // Finalize totals
        $out = array();
        foreach ($acc as $login => $pl) {
            $totalScore = 0;
            $mapsPlayed = 0;
            $totalTimeMs = 0;

            if (isset($pl['PerMap']) && is_array($pl['PerMap'])) {
                foreach ($pl['PerMap'] as $env => $mrow) {
                    $racePts = isset($mrow['BestRaceScore']) ? (int)$mrow['BestRaceScore'] : 0;
                    $lapPts  = isset($mrow['BestLapScore']) ? (int)$mrow['BestLapScore'] : 0;
                    $sumPts = $racePts + $lapPts;

                    $totalScore += $sumPts;
                    if ($sumPts !== 0) $mapsPlayed++; // A

                    // TotalTime = sum of BestRaceTimeMs across envs (only if >0)
                    $ms = isset($mrow['BestRaceTimeMs']) ? (int)$mrow['BestRaceTimeMs'] : 0;
                    if ($ms > 0) $totalTimeMs += $ms;
                }
            }

            $pl['Score'] = $totalScore;
            $pl['MapsPlayed'] = $mapsPlayed;
            $pl['TotalTimeMs'] = $totalTimeMs;
            $pl['TotalTime'] = self::formatTimeMs($totalTimeMs);

            $out[] = $pl;
        }

        // Sort + assign Rank
        usort($out, function($a, $b) {
            $aScore = isset($a['Score']) ? (int)$a['Score'] : 0;
            $bScore = isset($b['Score']) ? (int)$b['Score'] : 0;
            if ($aScore > $bScore) return -1;
            if ($aScore < $bScore) return 1;

            $aMp = isset($a['MapsPlayed']) ? (int)$a['MapsPlayed'] : 0;
            $bMp = isset($b['MapsPlayed']) ? (int)$b['MapsPlayed'] : 0;
            if ($aMp > $bMp) return -1;
            if ($aMp < $bMp) return 1;

            $aT = isset($a['TotalTimeMs']) ? (int)$a['TotalTimeMs'] : 0;
            $bT = isset($b['TotalTimeMs']) ? (int)$b['TotalTimeMs'] : 0;
            if ($aT > 0 && $bT > 0) {
                if ($aT < $bT) return -1;
                if ($aT > $bT) return 1;
            }

            return strcmp((string)$a['Login'], (string)$b['Login']);
        });

        $rank = 1;
        $count = count($out);
        for ($i = 0; $i < $count; $i++) {
            $out[$i]['Rank'] = $rank++;
        }

        return $out;
    }
    private static function formatTimeMs($ms) {
        $ms = (int)$ms;
        if ($ms <= 0) return '';

        $totalSeconds = (int)floor($ms / 1000);
        $centis = (int)floor(($ms % 1000) / 10);

        $minutes = (int)floor($totalSeconds / 60);
        $seconds = (int)($totalSeconds % 60);

        return sprintf('%02d:%02d.%02d', $minutes, $seconds, $centis);
    }

}

function buildRankByLogin(array $list, $scoreKey, $mapsPlayedKey)
{
    $sorted = $list;

    usort($sorted, function ($a, $b) use ($scoreKey, $mapsPlayedKey) {
        $aScore = isset($a[$scoreKey]) ? (int)$a[$scoreKey] : 0;
        $bScore = isset($b[$scoreKey]) ? (int)$b[$scoreKey] : 0;
        if ($aScore > $bScore) return -1;
        if ($aScore < $bScore) return 1;

        $aMp = isset($a[$mapsPlayedKey]) ? (int)$a[$mapsPlayedKey] : 0;
        $bMp = isset($b[$mapsPlayedKey]) ? (int)$b[$mapsPlayedKey] : 0;
        if ($aMp > $bMp) return -1;
        if ($aMp < $bMp) return 1;

        $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
        $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
        return strcmp($aLogin, $bLogin);
    });

    $rankByLogin = array();
    $n = count($sorted);
    for ($i = 0; $i < $n; $i++) {
        $login = isset($sorted[$i]['Login']) ? (string)$sorted[$i]['Login'] : '';
        if ($login !== '') {
            $rankByLogin[$login] = $i + 1;
        }
    }

    return $rankByLogin;
}