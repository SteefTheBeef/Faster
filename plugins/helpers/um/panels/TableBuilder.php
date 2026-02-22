<?php
require_once __DIR__ . '/../../utils/StringUtils.php';
require_once __DIR__ . '/../UMPanel.php';

class TableBuilder {
    static function build($layout, $rows, $reserveRightW, $topOffset, $columns, $preferredRowH = null) {
        $panelW = isset($layout['panelW']) ? (float)$layout['panelW'] : 0.0;
        $topY = isset($layout['panelBodyTopY']) ? (float)$layout['panelBodyTopY'] : -5.0;
        $bodyH = isset($layout['panelBodyHeight']) ? (float)$layout['panelBodyHeight'] : 0.0;

        $contentL = 1.2;
        $contentR = 1.2;

        // Limit table width so it stops BEFORE the submenu area
        $reserveRightW = (float)$reserveRightW;
        if ($reserveRightW < 0) $reserveRightW = 0;

        $tableX = $contentL;
        $tableW = $panelW - $contentL - $contentR - $reserveRightW;
        if ($tableW < 10.0) $tableW = 10.0;

        // Apply vertical offset (space taken by subheader)
        $topOffset = (float)$topOffset;
        if ($topOffset < 0) $topOffset = 0;

        // Fit vertically: compute row height from available panel body height.
        $rows = (int)$rows;
        if ($rows < 1) $rows = 1;

        $bottomPad = 1.0;
        $availableH = $bodyH - $bottomPad - $topOffset;
        if ($availableH < 0) $availableH = 0;

        $minRowH = 1.5;
        $maxRowH = 2.2;

        $rowH = $maxRowH;
        if ($preferredRowH !== null) {
            $preferredRowH = (float)$preferredRowH;
            if ($preferredRowH > 0) {
                $rowH = $preferredRowH;
            }
        }

        // If it doesn't fit, shrink to fit (header + rows)
        $needH = ($rows + 1) * $rowH;
        if ($needH > $availableH && $availableH > 0) {
            $rowH = $availableH / (float)($rows + 1);
        }

        // Clamp final row height
        if ($rowH > $maxRowH) $rowH = $maxRowH;
        if ($rowH < $minRowH) $rowH = $minRowH;

        $textSize = 0.8;
        $headerY = $topY - $topOffset;

        if (!is_array($columns)) $columns = array();
        $colCount = count($columns);
        if ($colCount < 1) return '';

        $colW = $tableW / (float)$colCount;

        $padX = 0.4;
        $headerFont = '$cf0$o';
        $cellFont = '$fff$o';

        $xml = '';

        // Header background
        $xml .= "<quad posn='{$tableX} {$headerY} 0.15' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='0006'/>";

        // Header labels
        $headerTextY = $headerY - ($rowH / 2.0);
        for ($c = 0; $c < $colCount; $c++) {
            $col = $columns[$c];

            $header = isset($col['header']) ? (string)$col['header'] : '';
            $halign = isset($col['halign']) ? (string)$col['halign'] : 'left';

            $xLeft = $tableX + ($c * $colW);
            $xText = ($halign === 'right') ? ($xLeft + $colW - $padX) : ($xLeft + $padX);

            $xml .= "<label posn='" . $xText . " {$headerTextY} 0.2' sizen='" . ($colW - 2 * $padX) . " {$rowH}' halign='{$halign}' valign='center' textsize='{$textSize}' text='{$headerFont}" . StringUtils::safeString($header) . "'/>";
        }

        // Rows
        for ($i = 0; $i < $rows; $i++) {
            $pos = $i;

            $rowY = $headerY - (($i + 1) * $rowH);
            $rowTextY = $rowY - ($rowH / 2.0);

            $bg = (($i % 2) === 0) ? '0003' : '0000';
            $xml .= "<quad posn='{$tableX} {$rowY} 0.10' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='{$bg}'/>";

            for ($c = 0; $c < $colCount; $c++) {
                $col = $columns[$c];

                $halign = isset($col['halign']) ? (string)$col['halign'] : 'left';

                $xLeft = $tableX + ($c * $colW);
                $xText = ($halign === 'right') ? ($xLeft + $colW - $padX) : ($xLeft + $padX);

                // Value rules:
                // 1) Explicit rank column: ['rank' => true]
                // 2) Data lookup column:   ['data' => array(...)] uses $data[$pos]
                // 3) Backward-friendly fallback: first column with no data becomes rank
                $value = '';
                if (isset($col['rank']) && $col['rank']) {
                    $value = (string)$pos;
                } elseif (isset($col['data']) && is_array($col['data'])) {
                    $data = $col['data'];
                    $value = isset($data[$pos]) ? (string)$data[$pos] : '';
                } elseif ($c === 0) {
                    $value = (string)$pos;
                }

                $xml .= "<label posn='" . $xText . " {$rowTextY} 0.2' sizen='" . ($colW - 2 * $padX) . " {$rowH}' halign='{$halign}' valign='center' textsize='{$textSize}' text='{$cellFont}" . StringUtils::safeString($value) . "'/>";
            }
        }

        return $xml;
    }
}