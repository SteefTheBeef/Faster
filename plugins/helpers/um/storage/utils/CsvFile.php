<?php

/**
 * CSV-backed best file format helper.
 * - Uses str_getcsv/fputcsv so commas/quotes in fields are safe.
 * - Still supports existing “old” files that were written by string concatenation (they parse as CSV rows).
 */
class CsvFile
{
    public static function parse($filePath, $spec)
    {
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

        $currentMapId = '';

        foreach ($lines as $line) {

            $line = trim((string)$line);
            if ($line === '') continue;

            // NOTE: map marker starts with '#', so don't skip it as a comment.
            // Skip comment lines, but keep "### MAP,..." rows.
            if ($line[0] === '#' && strpos($line, BestRaces::MAP_MARKER) !== 0) continue;

            $cols = str_getcsv($line);
            if (!is_array($cols) || count($cols) < 1) continue;

            // Map marker row: ### MAP,<env>,<mapId>,<mapName>,<author>
            //console("BestRaces::MAP_MARKER: " . BestRaces::MAP_MARKER); // prints 'BestRaces::MAP_MARKER: ### MAP'
            if ((string)$cols[0] === BestRaces::MAP_MARKER) {
                //console("BestRaces::MAP_MARKER found");
                $env = isset($cols[1]) ? (string)$cols[1] : '';
                $mapId = isset($cols[2]) ? (string)$cols[2] : '';
                $mapName = isset($cols[3]) ? (string)$cols[3] : '';
                $author = isset($cols[4]) ? (string)$cols[4] : '';

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

            // Column header row: Login,...
            if (isset($cols[0]) && (string)$cols[0] === 'Login') {
                continue;
            }

            if ($currentMapId === '') {
                continue;
            }

            $row = call_user_func($spec['parsePlayerRow'], $cols);
            if ($row === null) continue;

            $login = isset($row['Login']) ? (string)$row['Login'] : '';
            if ($login === '') continue;

            if (!isset($data[$currentMapId])) {
                $data[$currentMapId] = array('meta' => array(), 'players' => array());
            }
            if (!isset($data[$currentMapId]['players']) || !is_array($data[$currentMapId]['players'])) {
                $data[$currentMapId]['players'] = array();
            }

            $data[$currentMapId]['players'][$login] = $row;
        }

        return $data;
    }

    public static function writeAtomic($filePath, $data, $spec)
    {
        $fh = fopen('php://temp', 'r+');
        if ($fh === false) {
            return;
        }

        fwrite($fh, '# ' . $spec['title'] . "\n");
        fwrite($fh, '# UpdatedAt=' . date('Y-m-d H:i:s') . "\n");
        fwrite($fh, '# ' . $spec['sortComment'] . "\n\n");

        foreach ($data as $mapId => $section) {
            $meta = isset($section['meta']) ? $section['meta'] : array();

            $env = isset($meta['Environment']) ? (string)$meta['Environment'] : '';
            $mapName = isset($meta['MapName']) ? (string)$meta['MapName'] : '';
            $author = isset($meta['Author']) ? (string)$meta['Author'] : '';

            // No quotes, legacy style
            fwrite($fh, self::legacyLine(array(BestRaces::MAP_MARKER, $env, (string)$mapId, $mapName, $author)));

            fwrite($fh, self::legacyLine($spec['header']));

            $players = isset($section['players']) ? $section['players'] : array();
            foreach ($players as $row) {
                $cols = call_user_func($spec['serializePlayerRow'], $row);
                fwrite($fh, self::legacyLine($cols));
            }

            fwrite($fh, "\n");
        }

        rewind($fh);
        $out = stream_get_contents($fh);
        fclose($fh);

        FastFile::atomicWrite($filePath, $out, true);
    }
    /**
     * Writes a comma-separated line with NO quoting, ever.
     * Any commas/newlines/quotes in fields are stripped/replaced to keep the format stable.
     */
    private static function legacyLine($fields)
    {
        if (!is_array($fields)) {
            return '';
        }

        $out = array();
        $count = count($fields);
        for ($i = 0; $i < $count; $i++) {
            $out[] = self::legacyField(isset($fields[$i]) ? $fields[$i] : '');
        }

        return implode(',', $out) . "\n";
    }

    private static function legacyField($v)
    {
        $v = (string)$v;

        // Keep it one-line
        $v = str_replace(array("\r", "\n", "\t"), ' ', $v);

        // Never allow commas (would shift columns)
        $v = str_replace(',', ' ', $v);

        // Never emit quotes (your requirement)
        $v = str_replace('"', '', $v);

        // Optional: trim to keep it tidy
        return trim($v);
    }
}