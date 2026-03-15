<?php

class MapRatings {

    static function getTARatings() {
        return self::parseRatingTAFile('fastlog/um/quali/ratingTA.txt');
    }
    static function parseRatingTAFile($filePath) {
        $result = array();

        if (!is_readable($filePath)) {
            return $result;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            return $result;
        }

        while (($line = fgets($handle)) !== false) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // Skip comments / headers
            if (strpos($line, '#') === 0) {
                continue;
            }

            // Skip block separators
            if ($line === '----------------') {
                continue;
            }

            // Skip CSV header line
            if ($line === 'Login,ChallengeID,Environment,UpdatedAt') {
                continue;
            }

            $parts = explode(',', $line);

            if (count($parts) < 4) {
                continue;
            }

            $login = trim($parts[0]);

            if ($login === '') {
                continue;
            }

            if (!isset($result[$login])) {
                $result[$login] = array();
            }
            $challengeId = trim($parts[1]);
            $result[$login][$challengeId] = array(
                'ChallengeID' => trim($parts[1]),
                'Environment' => trim($parts[2]),
                'UpdatedAt' => trim($parts[3]),
            );
        }

        fclose($handle);

        // add rankings for convenience.
        foreach ($result as $login => &$playerRatings) {
            $i = 0;
            foreach ($playerRatings as $mapId => &$mapRating) {
                $mapRating['Rank'] = $i;
                $i++;
            }
        }

        return $result;
    }

    static function saveRatingsToFile($allRatings) {
        $filePath = 'fastlog/um/ratingTA.txt';

        if (!is_array($allRatings)) {
            return false;
        }

        $content = '';
        $content .= "# UM TA Map Ratings\n";
        $content .= "# UpdatedAt=" . date('Y-m-d H:i:s') . "\n";
        $content .= "\n";
        $content .= "### Map ratings\n";
        $content .= "Login,ChallengeID,Environment,UpdatedAt\n";

        $playerIndex = 0;
        foreach ($allRatings as $currentLogin => $playerRatings) {
            if (!is_array($playerRatings) || empty($playerRatings)) {
                continue;
            }

            $currentLogin = trim($currentLogin);
            if ($currentLogin === '') {
                continue;
            }

            if ($playerIndex > 0) {
                $content .= "----------------\n";
            }

            foreach ($playerRatings as $challengeId => $rating) {
                if (!is_array($rating)) {
                    continue;
                }

                $currentChallengeId = isset($rating['ChallengeID']) && trim($rating['ChallengeID']) !== ''
                    ? trim($rating['ChallengeID'])
                    : trim($challengeId);

                if ($currentChallengeId === '') {
                    continue;
                }

                $content .= $currentLogin . ','
                    . $currentChallengeId . ','
                    . (isset($rating['Environment']) ? trim($rating['Environment']) : '') . ','
                    . (isset($rating['UpdatedAt']) && trim($rating['UpdatedAt']) !== ''
                        ? trim($rating['UpdatedAt'])
                        : date('Y-m-d H:i:s'))
                    . "\n";
            }

            $playerIndex++;
        }

        return file_put_contents($filePath, $content, LOCK_EX) !== false;
    }

}