<?php

class UmPlayers {

    /**
     * Update the UM players file (single file) from a players list.
     *
     * Expected $playersList entries to have:
     * - Login
     * - NickName (colored)
     * - IsSpectator (optional)
     */
    static function updatePlayersFile($playersList) {
        if (!is_array($playersList) || count($playersList) < 1) return;

        $dir = 'fastlog/um';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }

        $filePath = $dir . '/players.txt';

        if (!file_exists($filePath)) {
            $h = @fopen($filePath, 'a');
            if ($h) fclose($h);
        }

        $data = self::parsePlayersFile($filePath);

        $now = date('Y-m-d H:i:s');
        foreach ($playersList as $p) {
            if (!is_array($p)) continue;

            if (isset($p['IsSpectator']) && (int)$p['IsSpectator'] === 1) {
                continue;
            }

            $login = isset($p['Login']) ? stripColors($p['Login']) : '';
            if ($login === '') continue;

            $nickWithColor = isset($p['NickName']) ? (string)$p['NickName'] : '';
            $nick = $nickWithColor !== '' ? stripColors($nickWithColor) : '';

            // Strip commas (CSV-like format) and also remove line breaks to keep 1 player = 1 line.
            $nick = str_replace(array(',', "\r", "\n"), '', $nick);
            $nickWithColor = str_replace(array(',', "\r", "\n"), '', $nickWithColor);

            // If nickname is missing, don't overwrite a previously known one.
            $old = isset($data[$login]) ? $data[$login] : null;

            $row = array(
                'Login' => $login,
                'NickName' => $nick !== '' ? $nick : ($old !== null && isset($old['NickName']) ? (string)$old['NickName'] : ''),
                'NickNameWithColor' => $nickWithColor !== '' ? $nickWithColor : ($old !== null && isset($old['NickNameWithColor']) ? (string)$old['NickNameWithColor'] : ''),
                'UpdatedAt' => $now,
            );

            $data[$login] = $row;
        }

        // Sort by Login ASC
        ksort($data, SORT_STRING);

        self::writePlayersFile($filePath, $data);
    }

    /**
     * Load players.txt as a map: login => row (NickName / NickNameWithColor).
     */
    static function loadPlayersNicknamesMap() {
        $filePath = 'fastlog/um/players.txt';
        $data = self::parsePlayersFile($filePath);

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
     * Login,NickName,NickNameWithColor,UpdatedAt
     * ...
     */
    private static function parsePlayersFile($filePath) {
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
            $nick = isset($cols[1]) ? (string)$cols[1] : '';
            $nickWithColor = isset($cols[2]) ? (string)$cols[2] : '';
            $updatedAt = isset($cols[3]) ? (string)$cols[3] : '';

            $data[$login] = array(
                'Login' => $login,
                'NickName' => $nick,
                'NickNameWithColor' => $nickWithColor,
                'UpdatedAt' => $updatedAt,
            );
        }

        return $data;
    }

    private static function writePlayersFile($filePath, $data) {
        $out = '';
        $out .= "# UM Players\n";
        $out .= "# UpdatedAt=" . date('Y-m-d H:i:s') . "\n";
        $out .= "# Sort: Login\n\n";
        $out .= "###\n";
        $out .= "Login,NickName,NickNameWithColor,UpdatedAt\n";

        foreach ($data as $login => $row) {
            $login = isset($row['Login']) ? (string)$row['Login'] : '';
            if ($login === '') continue;

            $nick = isset($row['NickName']) ? (string)$row['NickName'] : '';
            $nickWithColor = isset($row['NickNameWithColor']) ? (string)$row['NickNameWithColor'] : '';
            $updatedAt = isset($row['UpdatedAt']) ? (string)$row['UpdatedAt'] : '';

            $out .= $login . "," . $nick . "," . $nickWithColor . "," . $updatedAt . "\n";
        }

        // atomic-ish write with lock
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
}