<?php

class Donations {


    /**
     * Load donations.txt as a map: login => row (NickName / NickNameWithColor).
     */
    static function loadDonations() {
        $filePath = 'fastlog/um/config/donations.txt';
        $data = self::parseDonationsFile($filePath);

        // Ensure keys are normalized (strip colors just in case)
        $out = array();
        foreach ($data as $login => $row) {
            $safeLogin = $login !== '' ? stripColors($login) : '';
            if ($safeLogin === '') continue;
            $out[$safeLogin] = $row;
        }
        return $out;
    }

    /**
     * File format:
     * # UM Players
     * # UpdatedAt=YYYY-mm-dd HH:ii:ss
     * # Sort: Login
     *
     * ###
     * Login, NickNameWithColor, Amount
     * ...
     */
    private static function parseDonationsFile($filePath) {
        $data = array();
        if (!file_exists($filePath)) return $data;

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false || !is_array($lines)) return $data;

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') continue;
            if ($line[0] === '#') continue;
            if (strpos($line, '###') === 0) continue;

            // header line
            if (strpos($line, 'Login,') === 0) continue;

            $cols = explode(',', $line);
            if (count($cols) < 1) continue;

            $login = isset($cols[0]) ? (string)$cols[0] : '';
            if ($login === '') continue;

            // NOTE: This is "CSV-like": commas inside nicknames are not supported.
            $nickWithColor = isset($cols[1]) ? (string)$cols[1] : '';
            $amount = isset($cols[2]) ? (float)$cols[2] : '';

            $data[$login] = array(
                'Login' => $login,
                'NickNameWithColor' => $nickWithColor,
                'Amount' => $amount,
            );
        }

        return $data;
    }
}