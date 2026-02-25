<?php
class ScoresMerger {
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
                            'scoreBestRace' => 0,      // recomputed at finalize
                            'scoreBestLap' => 0,       // recomputed at finalize
                            '_perMapBestRace' => array(), // uid => points (races only)
                            '_perMapBestLap' => array(),  // uid => points (laps only)
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

                $row['scoreBestRace'] = $scoreBestRace;
                $row['scoreBestLap'] = $scoreBestLap;

                // internal helpers for tie-breakers in sub-ranks
                $row['_mapsPlayedBestRace'] = $mapsPlayedBestRace;
                $row['_mapsPlayedBestLap'] = $mapsPlayedBestLap;

                $list[] = $row;
            }

            // --- compute rankBestRace / rankBestLap (within this env) ---
            $rankBestRaceByLogin = array();
            $raceSorted = $list;
            usort($raceSorted, function($a, $b) {
                $aScore = isset($a['scoreBestRace']) ? (int)$a['scoreBestRace'] : 0;
                $bScore = isset($b['scoreBestRace']) ? (int)$b['scoreBestRace'] : 0;
                if ($aScore > $bScore) return -1;
                if ($aScore < $bScore) return 1;

                $aMp = isset($a['_mapsPlayedBestRace']) ? (int)$a['_mapsPlayedBestRace'] : 0;
                $bMp = isset($b['_mapsPlayedBestRace']) ? (int)$b['_mapsPlayedBestRace'] : 0;
                if ($aMp > $bMp) return -1;
                if ($aMp < $bMp) return 1;

                $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
                $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
                return strcmp($aLogin, $bLogin);
            });
            for ($i = 0; $i < count($raceSorted); $i++) {
                $login = isset($raceSorted[$i]['Login']) ? (string)$raceSorted[$i]['Login'] : '';
                if ($login !== '') $rankBestRaceByLogin[$login] = $i + 1;
            }

            $rankBestLapByLogin = array();
            $lapSorted = $list;
            usort($lapSorted, function($a, $b) {
                $aScore = isset($a['scoreBestLap']) ? (int)$a['scoreBestLap'] : 0;
                $bScore = isset($b['scoreBestLap']) ? (int)$b['scoreBestLap'] : 0;
                if ($aScore > $bScore) return -1;
                if ($aScore < $bScore) return 1;

                $aMp = isset($a['_mapsPlayedBestLap']) ? (int)$a['_mapsPlayedBestLap'] : 0;
                $bMp = isset($b['_mapsPlayedBestLap']) ? (int)$b['_mapsPlayedBestLap'] : 0;
                if ($aMp > $bMp) return -1;
                if ($aMp < $bMp) return 1;

                $aLogin = isset($a['Login']) ? (string)$a['Login'] : '';
                $bLogin = isset($b['Login']) ? (string)$b['Login'] : '';
                return strcmp($aLogin, $bLogin);
            });
            for ($i = 0; $i < count($lapSorted); $i++) {
                $login = isset($lapSorted[$i]['Login']) ? (string)$lapSorted[$i]['Login'] : '';
                if ($login !== '') $rankBestLapByLogin[$login] = $i + 1;
            }

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
}