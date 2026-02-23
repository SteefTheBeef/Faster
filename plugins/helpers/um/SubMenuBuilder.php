<?php
require_once 'layout/Layout.php';
require_once __DIR__ . '/../utils/StringUtils.php';

class SubMenuBuilder {
    /**
     * Build a generic "sub menu on the right" INSIDE the panel body.
     * Returns array('xml' => ..., 'contentW' => float, 'submenuW' => float)
     */
    static function build($login, Layout $layout, $menuKey, $items, $opts = array()) {
        global $_players, $_ml_act;

        $panelW = (float)$layout->geometry->panelWidth;
        $topY = (float)$layout->geometry->panelBodyTopY;

        $contentL = isset($opts['contentL']) ? (float)$opts['contentL'] : 1.2;
        $contentR = isset($opts['contentR']) ? (float)$opts['contentR'] : 1.2;
        $gap = isset($opts['gap']) ? (float)$opts['gap'] : 0.0;

        $submenuW = isset($opts['submenuW']) ? (float)$opts['submenuW'] : 18.0;
        $rowH = isset($opts['rowH']) ? (float)$opts['rowH'] : 2.8;
        $padX = isset($opts['padX']) ? (float)$opts['padX'] : 0.7;
        $padY = isset($opts['padY']) ? (float)$opts['padY'] : 0.8;

        // NEW: submenu's own right margin (defaults to old behavior)
        $submenuR = isset($opts['submenuR']) ? (float)$opts['submenuR'] : $contentR;

        // clamp submenu width
        $maxSubW = $panelW - $contentL - $gap - $submenuR;
        if ($maxSubW < 0) $maxSubW = 0;
        if ($submenuW > $maxSubW) $submenuW = $maxSubW;

        // Place submenu flush to the right border when $submenuR = 0
        $submenuX = $panelW - $submenuR - $submenuW;

        $contentW = $submenuX - $gap - $contentL;
        if ($contentW < 0) $contentW = 0;

        $stateKey = 'um.subtab.' . $menuKey;
        $activeSub = isset($_players[$login]['ML'][$stateKey]) ? (string)$_players[$login]['ML'][$stateKey] : '';

        if ($activeSub === '' && isset($items[0]['key'])) {
            $activeSub = (string)$items[0]['key'];
            $_players[$login]['ML'][$stateKey] = $activeSub;
        }

        $xml = '';

        // Items
        $y = $topY - 3.2;
        for ($i = 0; $i < count($items); $i++) {
            $itKey = isset($items[$i]['key']) ? (string)$items[$i]['key'] : ('item' . $i);
            $itTitle = isset($items[$i]['title']) ? (string)$items[$i]['title'] : $itKey;
            $itActionName = isset($items[$i]['action']) ? (string)$items[$i]['action'] : '';
            $itActionId = ($itActionName !== '' && isset($_ml_act[$itActionName])) ? (int)$_ml_act[$itActionName] : 0;

            $isActive = ($itKey === $activeSub);
            $bg = $isActive ? '060D' : '010D';

            $xml .= "<quad posn='{$submenuX} {$y} 0.14' sizen='{$submenuW} {$rowH}' halign='left' valign='top' bgcolor='{$bg}' action='{$itActionId}'/>";

            // Center text vertically in the row
            $textY = $y - ($rowH / 2.0);

            $xml .= "<label posn='" . ($submenuX + $padX) . " {$textY} 0.20' sizen='" . ($submenuW - 2 * $padX) . " {$rowH}' halign='left' valign='center' textsize='1' text='\$fff\$o" . StringUtils::safeString($itTitle) . "'/>";

            $y -= ($rowH);
        }

        return array(
            'xml' => $xml,
            'contentW' => $contentW,
            'submenuW' => $submenuW,
        );
    }
}