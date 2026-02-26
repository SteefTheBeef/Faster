<?php

define('RACES_PER_PAGE', 7);
define('PLAYERS_PER_PAGE', 16);


class UMPanel {
    static function playersPageCount(array $players) {
        $total = count($players);
        if ($total <= 0) return 1;
        return (int)ceil($total / PLAYERS_PER_PAGE);
    }
    static function racesPageCount(array $races) {
        $total = count($races);
        if ($total <= 0) return 1;
        return (int)ceil($total / RACES_PER_PAGE);
    }

    static function clampInt($v, $min, $max) {
        $v = (int)$v;
        if ($v < $min) return $min;
        if ($v > $max) return $max;
        return $v;
    }

    static function racesSliceForPage(array $races, $page /* 0-based */) {
        $offset = ((int)$page) * RACES_PER_PAGE;
        return array_slice($races, $offset, RACES_PER_PAGE);
    }

    static function playersSliceForPage(array $players, $page /* 0-based */) {
        $offset = ((int)$page) * PLAYERS_PER_PAGE;
        return array_slice($players, $offset, PLAYERS_PER_PAGE);
    }

    static function mlStripCodes($s) {
        // Remove $xxx color codes, $o/$i/$n style toggles, etc.
        // This is a simple heuristic; adjust if you use more codes.
        return preg_replace('/\$(?:[0-9a-fA-F]{1,3}|[a-zA-Z])/', '', $s);
    }

    static function mlEstimateTextWidth($text, $textsize) {
        $plain = UMPanel::mlStripCodes($text);
        $len = strlen($plain);

        // Tune this constant once by eyeballing a few labels in-game.
        // Bigger => wider tabs. Typical range: 0.45 .. 0.70
        $perChar = 0.55;

        return $len * $perChar * (float)$textsize;
    }

    static function mlTabWidth($text, $textsize, $padLeftRight, $minW, $maxW) {
        $w = UMPanel::mlEstimateTextWidth($text, $textsize) + (2.0 * $padLeftRight);
        if ($w < $minW) $w = $minW;
        if ($w > $maxW) $w = $maxW;
        return $w;
    }

    static function textLabel(Layout $layout, $text, $yOffset = 0, $bold = false) {
        $font = $bold ? '$o' : '';
        return  "<label posn='1 ". ($layout->geometry->panelBodyTopY - $yOffset) . " 0.2' sizen='" . ($layout->geometry->panelWidth/1.5 - 2) . " {$layout->geometry->panelBodyHeight}' halign='left' valign='top' textsize='1' autonewline='1' text='{$font}" . StringUtils::safeString($text) . "'/>";

    }

}