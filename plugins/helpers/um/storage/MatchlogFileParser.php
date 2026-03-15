<?php
// Reading matchlog file for playoffs live events
class MatchlogFileParser {
    static function getScoreboardPlayersFromMatchlog($filePath, $pointsScale) {
        $races = self::parseMatchlogFile($filePath);
        if (!$races || count($races) < 1) {
            return array();
        }

        // Accumulate points over ALL races
        $totalPointsByLogin = array();
        $playersMetaByLogin = array(); // store latest known nick data (or first seen)
        $racesByLogin = array(); // store per-player races (multi-dimensional array)

        foreach ($races as $raceIndex => $race) {
            $playersByLogin = isset($race['Players']) ? $race['Players'] : array();
            $scoresByLogin  = isset($race['Scores']) ? $race['Scores'] : array();
            $raceInfo       = isset($race['RaceInfo']) ? $race['RaceInfo'] : array();

            // Keep player meta (nickname) for output
            foreach ($playersByLogin as $login => $p) {
                if (!isset($playersMetaByLogin[$login])) {
                    $playersMetaByLogin[$login] = $p;
                } else {
                    // prefer most recent non-empty values
                    if (isset($p['NickNameWithColor']) && $p['NickNameWithColor'] !== '') {
                        $playersMetaByLogin[$login]['NickNameWithColor'] = $p['NickNameWithColor'];
                    }
                    if (isset($p['NickName']) && $p['NickName'] !== '') {
                        $playersMetaByLogin[$login]['NickName'] = $p['NickName'];
                    }
                }
            }

            // Award points for this race by Rank using $pointsScale
            foreach ($scoresByLogin as $login => $s) {
                $rank = isset($s['Rank']) ? (int)$s['Rank'] : 0;
                $award = 0;

                if ($rank >= 1 && is_array($pointsScale) && isset($pointsScale[$rank])) {
                    // pointsScale starts from index 0
                    $award = (int)$pointsScale[$rank-1];
                }

                if (!isset($totalPointsByLogin[$login])) {
                    $totalPointsByLogin[$login] = 0;
                }
                $totalPointsByLogin[$login] += $award;

                // Attach this race to the player
                if (!isset($racesByLogin[$login])) {
                    $racesByLogin[$login] = array();
                }

                $racesByLogin[$login][] = array(
                    'RaceIndex' => (int)$raceIndex,

                    // Race info (common for everyone)
                    'RaceInfo' => array(
                        'Date' => isset($raceInfo['Date']) ? $raceInfo['Date'] : '',
                        'ChallengeName' => isset($raceInfo['ChallengeName']) ? $raceInfo['ChallengeName'] : '',
                        'ChallengeNameWithColor' => isset($raceInfo['ChallengeNameWithColor']) ? $raceInfo['ChallengeNameWithColor'] : '',
                        'Environment' => isset($raceInfo['Environment']) ? $raceInfo['Environment'] : '',
                        'NumberOfLaps' => isset($raceInfo['NumberOfLaps']) ? $raceInfo['NumberOfLaps'] : '',
                    ),

                    // Player's score row for this race (as parsed from "* Scores:")
                    'Score' => $s,

                    // Computed fields you’ll likely want later
                    'Rank' => (int)$rank,
                    'AwardedPoints' => (int)$award,
                );
            }
        }

        // Merge for output: one row per login
        $merged = array();
        foreach ($totalPointsByLogin as $login => $totalPts) {
            $p = isset($playersMetaByLogin[$login]) ? $playersMetaByLogin[$login] : array();

            $merged[] = array(
                'Login' => $login,
                'Rank' => 9999, // rank is per-race; total board will be sorted by Points
                'Score' => (int)$totalPts,
                'NickNameWithColor' => isset($p['NickNameWithColor']) ? $p['NickNameWithColor'] : (isset($p['NickName']) ? $p['NickName'] : $login),
                'NickName' => isset($p['NickName']) ? $p['NickName'] : $login,

                // New: all races for this player
                'Races' => isset($racesByLogin[$login]) ? $racesByLogin[$login] : array(),
            );
        }

        // Sort by total Points desc, tie-breaker by NickName asc (stable-ish)
        usort($merged, 'sortByPointsDescThenNameAsc');

        return $merged;
    }


    /**
     * Parses a matchlog file containing several races separated by lines containing "###".
     * Each race may contain subsections "* Scores:", "* Players:", "* Race info:".
     *
     * Returns: array of races, each like:
     *   array(
     *     'scores' => array(login => rowArray),
     *     'players' => array(login => rowArray),
     *     'raceInfo' => array(column => value),
     *   )
     */
    static function parseMatchlogFile($filePath) {
        if (!is_string($filePath) || $filePath === '' || !file_exists($filePath)) {
            return array();
        }

        $text = @file_get_contents($filePath);
        if ($text === false || $text === '') {
            return array();
        }

        // Normalize newlines for easier parsing
        $text = str_replace("\r\n", "\n", $text);
        $text = str_replace("\r", "\n", $text);

        // Split by delimiter line "###" (with optional surrounding whitespace)
        $parts = preg_split('/^\s*###\s*$/m', $text);
        if (!$parts || count($parts) < 1) {
            return array();
        }

        $races = array();

        foreach ($parts as $part) {
            $
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $race = array(
                'Scores' => array(),
                'Players' => array(),
                'RaceInfo' => array(),
            );
            $maxNumberOfLaps = 0;
            $scoresBlock = self::extractSectionBlock($part, '* Scores:');
            if ($scoresBlock !== '') {
                $race['Scores'] = self::parseCsvSectionByLogin($scoresBlock);
                foreach ($race['Scores'] as $login => $score) {
                    $maxNumberOfLaps = max($maxNumberOfLaps, $score['Lap']);
                }
            }

            $playersBlock = self::extractSectionBlock($part, '* Players:');
            if ($playersBlock !== '') {
                $race['Players'] = self::parseCsvSectionByLogin($playersBlock);
            }

            $raceInfoBlock = self::extractSectionBlock($part, '* Race info:');
            if ($raceInfoBlock !== '') {
                $race['RaceInfo'] = self::parseSingleCsvRow($raceInfoBlock);
            }

            // filter out races that don't have any player who finished the race'
            if ($race['RaceInfo']['NumberOfLaps'] === $maxNumberOfLaps) {
                $races[] = $race;
            }

        }

        return $races;
    }

    /**
     * Extracts the content of a section that starts with an exact marker line, and ends at the next "--------------------".
     * Returns block content WITHOUT the marker line itself.
     */
    private static function extractSectionBlock($raceText, $marker) {
        $raceText = str_replace("\r\n", "\n", $raceText);
        $raceText = str_replace("\r", "\n", $raceText);

        $pos = strpos($raceText, $marker);
        if ($pos === false) {
            return '';
        }

        $after = substr($raceText, $pos + strlen($marker));
        $after = ltrim($after, "\n");

        $endPos = strpos($after, "--------------------");
        if ($endPos === false) {
            // Section might be last; take the rest
            return trim($after);
        }

        return trim(substr($after, 0, $endPos));
    }

    /**
     * Parses a CSV section where the first column is "Login" and returns:
     *   array(login => array(columnName => value, ...))
     */
    private static function parseCsvSectionByLogin($block) {
        $lines = preg_split("/\n+/", trim($block));
        if (!$lines || count($lines) < 2) {
            return array();
        }

        $header = self::csvSplitLine(array_shift($lines));
        if (!$header || count($header) < 1) {
            return array();
        }

        // Trim header cells
        for ($i = 0; $i < count($header); $i++) {
            $header[$i] = trim($header[$i]);
        }

        $out = array();
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $row = self::csvSplitLine($line);
            if (!$row || count($row) < 1) {
                continue;
            }

            $assoc = array();
            for ($i = 0; $i < count($header); $i++) {
                $col = $header[$i];
                $assoc[$col] = isset($row[$i]) ? trim($row[$i]) : '';
            }

            if (!isset($assoc['Login']) || $assoc['Login'] === '') {
                continue;
            }

            $login = $assoc['Login'];
            $out[$login] = $assoc;
        }

        return $out;
    }

    /**
     * Parses a section that is exactly:
     *   Col1, Col2, ...
     *   v1, v2, ...
     * Returns associative array column => value (single row).
     */
    private static function parseSingleCsvRow($block) {
        $lines = preg_split("/\n+/", trim($block));
        if (!$lines || count($lines) < 2) {
            return array();
        }

        $header = self::csvSplitLine(trim($lines[0]));
        $row = self::csvSplitLine(trim($lines[1]));

        if (!$header || !$row) {
            return array();
        }

        $out = array();
        for ($i = 0; $i < count($header); $i++) {
            $col = trim($header[$i]);
            if ($col === '') {
                continue;
            }
            $out[$col] = isset($row[$i]) ? trim($row[$i]) : '';
        }

        return $out;
    }

    /**
     * Simple CSV split by comma (your matchlog format does not quote fields).
     * If you later add quoting, swap this with str_getcsv($line).
     */
    private static function csvSplitLine($line) {
        // Keep it conservative: trim and split by commas
        $line = trim($line);
        if ($line === '') {
            return array();
        }
        return explode(',', $line);
    }

}

/**
 * Sort helper: points DESC, then name ASC
 */
function sortByPointsDescThenNameAsc($a, $b) {
    $pa = isset($a['Score']) ? (int)$a['Score'] : 0;
    $pb = isset($b['Score']) ? (int)$b['Score'] : 0;

    if ($pa !== $pb) {
        return ($pa > $pb) ? -1 : 1;
    }

    $na = isset($a['NickName']) ? (string)$a['NickName'] : '';
    $nb = isset($b['NickName']) ? (string)$b['NickName'] : '';
    return strcmp($na, $nb);
}

function sortByRankAsc($a, $b) {
    $ra = isset($a['Rank']) ? (int)$a['Rank'] : 9999;
    $rb = isset($b['Rank']) ? (int)$b['Rank'] : 9999;
    if ($ra === $rb) {
        return 0;
    }
    return ($ra < $rb) ? -1 : 1;
}