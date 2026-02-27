<?php

class Table {
    /**
     * Render a table.
     *
     * @param array $spec Table spec (positions, colors, columns, etc.)
     * @param array $rows Normalized rows (array of arrays)
     * @param array $ctx  Any extra context you want in callbacks (layout, actions, etc.)
     * @return string XML
     */
    public static function render(array $spec, array $rows) {
        $xml = '';

        $x = (float)$spec['x'];
        $yTop = (float)$spec['yTop'];
        $w = (float)$spec['w'];
        $rowH = (float)$spec['rowH'];

        $bgHeader = isset($spec['bgHeader']) ? (string)$spec['bgHeader'] : '0006';
        $bgEven = isset($spec['bgRowEven']) ? (string)$spec['bgRowEven'] : '0003';
        $bgOdd  = isset($spec['bgRowOdd'])  ? (string)$spec['bgRowOdd']  : '0000';

        $columns = isset($spec['columns']) ? $spec['columns'] : array();

        // Precompute x positions for columns
        $colPos = self::computeColumnPositions($x, $columns);

        // Header background quad
        $xml .= XmlTag::quad($x, $yTop, $w, $rowH, $bgHeader);

        // Header labels
        $cCount = count($columns);
        for ($c = 0; $c < $cCount; $c++) {
            $col = $columns[$c];
            $colX = $colPos[$c]['x'];
            $colW = $colPos[$c]['w'];

            $title = isset($col['title']) ? $col['title'] : '';
            $headerFont = isset($col['headerFont']) ? $col['headerFont'] : $spec['headerFont'];

            $padL = isset($col['padL']) ? (float)$col['padL'] : 0.0;
            $padR = isset($col['padR']) ? (float)$col['padR'] : 0.0;

            $labelX = $colX + $padL;
            $labelW = $colW - $padL - $padR;
            $labelY = $yTop - 0.6;

            $align = isset($col['align']) ? $col['align'] : 'left';
            if ($align === 'right') {
                // For right-aligned header, use the "right edge" convention if your XmlTag expects it,
                // otherwise you can just call labelRight with $labelX + $labelW.
                $xml .= XmlTag::labelRight($labelX + $labelW, $labelY, $labelW, $rowH, $headerFont . $title);
            } else {
                $xml .= XmlTag::label($labelX, $labelY, $labelW, $rowH, $headerFont . $title);
            }
        }

        // Rows
        $rCount = count($rows);
        for ($i = 0; $i < $rCount; $i++) {
            $row = $rows[$i];
            $rowY = $yTop - (($i + 1) * $rowH);

            $bg = (($i % 2) === 0) ? $bgOdd: $bgEven;
            $xml .= XmlTag::quad($x, $rowY, $w, $rowH, $bg);

            for ($c = 0; $c < $cCount; $c++) {
                $col = $columns[$c];
                $colX = $colPos[$c]['x'];
                $colW = $colPos[$c]['w'];

                $padL = isset($col['padL']) ? (float)$col['padL'] : 0.0;
                $padR = isset($col['padR']) ? (float)$col['padR'] : 0.0;

                $labelX = $colX + $padL;
                $labelW = $colW - $padL - $padR;
                $labelY = $rowY - 0.6;

                $cellFont = '';
                if (isset($col['cellFont']) && is_callable($col['cellFont'])) {
                    $cellFont = (string)call_user_func($col['cellFont'], $row, $i);
                } elseif (isset($spec['cellFont'])) {
                    $cellFont = (string)$spec['cellFont'];
                }

                $text = '';
                if (isset($col['value']) && is_callable($col['value'])) {
                    $text = (string)call_user_func($col['value'], $row, $i);
                } else {
                    $key = isset($col['key']) ? (string)$col['key'] : '';
                    $text = isset($row[$key]) ? (string)$row[$key] : '';
                }

                $align = isset($col['align']) ? (string)$col['align'] : 'left';
                if ($align === 'right') {
                    $xml .= XmlTag::labelRight($labelX + $labelW, $labelY, $labelW, $rowH, $cellFont . $text);
                } else {
                    $xml .= XmlTag::label($labelX, $labelY, $labelW, $rowH, $cellFont . $text);
                }
            }
        }

        // Optional pager hook (keep it simple: spec provides ready-to-render callback)
        //if (isset($spec['pager']) && is_callable($spec['pager'])) {
        //    $xml .= (string)call_user_func($spec['pager'], $rCount, $ctx, $spec);
        //}

        return $xml;
    }

    /**
     * Compute x positions by walking columns with explicit widths.
     *
     * @param float $tableX
     * @param array $columns
     * @return array<int, array{x:float,w:float}>
     */
    private static function computeColumnPositions($tableX, array $columns) {
        $out = array();
        $x = (float)$tableX;

        $count = count($columns);
        for ($i = 0; $i < $count; $i++) {
            $col = $columns[$i];
            $w = isset($col['width']) ? (float)$col['width'] : 0.0;

            $out[] = array('x' => $x, 'w' => $w);

            $gutterAfter = isset($col['gutterAfter']) ? (float)$col['gutterAfter'] : 0.0;
            $x += $w + $gutterAfter;
        }

        return $out;
    }
}