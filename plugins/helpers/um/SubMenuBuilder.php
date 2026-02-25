<?php
require_once 'layout/Layout.php';
require_once __DIR__ . '/../utils/StringUtils.php';
require_once __DIR__ . '/UmPanelKeys.php';

class SubMenuBuilder {


    /**
     * Backward compatible entry point.
     *
     * opts:
     *  - placement: 'right' (default) or 'bottom'
     */
    public static function build($login, Layout $layout, $menuKey, $items, $opts = array()) {
        $placement = isset($opts['placement']) ? (string)$opts['placement'] : 'right';
        if ($placement === 'bottom') {
            return self::buildBottom($login, $layout, $menuKey, $items, $opts);
        }
        return self::buildRight($login, $layout, $menuKey, $items, $opts);
    }

    /**
     * Right-side vertical submenu (current behavior).
     */
    public static function buildRight($login, Layout $layout, $menuKey, $items, $opts = array()) {
        $p = self::prepare($login, $layout, $menuKey, $items, $opts);

        $xml = '';
        $y = $p['topY'] - 3.2;
        for ($i = 0; $i < $p['count']; $i++) {
            if (!isset($items[$i]['action'])) continue;

            $itActionName = (string)$items[$i]['action'];
            $isActive = ($p['activeAction'] === $itActionName);
            $itActionId = isset($p['mlAct'][$itActionName]) ? (int)$p['mlAct'][$itActionName] : 0;

            $bg = $isActive ? '060D' : '010D';
            $xml .= XmlTag::quad($p['submenuX'], $y, $p['submenuW'], $p['rowH'], $bg, $itActionId, array('z' => 0.14));

            $textY = $y - ($p['rowH'] / 2.0);
            $title = isset($items[$i]['title']) ? $items[$i]['title'] : '';
            $xml .= XmlTag::labelCenterLeft(
                $p['submenuX'] + $p['padX'],
                $textY,
                $p['submenuW'] - 2 * $p['padX'],
                $p['rowH'],
                "\$fff\$o" . StringUtils::safeString($title),
                null,
                array('z' => 0.2)
            );

            $y -= $p['rowH'];
        }

        return array(
            'xml' => $xml,
            'contentW' => $p['contentW'],
            'submenuW' => $p['submenuW'],
            'stateKey' => $p['stateKey'],
            'activeAction' => $p['activeAction'],
        );
    }

    /**
     * Bottom horizontal submenu strip, anchored to the right.
     *
     * opts:
     *  - bottomY (float)     : explicit top Y of the strip (easiest to tune)
     *  - bottomMargin (float): used only if bottomY not provided, relative to -panelH
     *  - itemGap (float)     : optional spacing between tabs
     */
    public static function buildBottom($login, Layout $layout, $menuKey, $items, $opts = array()) {

        $autoWidth = isset($opts['autoWidth']) ? (bool)$opts['autoWidth'] : true;

        $tabGap = isset($opts['itemGap']) ? (float)$opts['itemGap'] : 0.2;
        $tabTextsize = isset($opts['textsize']) ? (float)$opts['textsize'] : 1.10;
        $tabPadLR = isset($opts['tabPadLR']) ? (float)$opts['tabPadLR'] : 0.7;
        $tabMinW = isset($opts['tabMinW']) ? (float)$opts['tabMinW'] : 3.4;
        $tabMaxW = isset($opts['tabMaxW']) ? (float)$opts['tabMaxW'] : 12.0;

        // NEW: optionally stretch to use all available width (anchored right)
        $fill = isset($opts['fill']) ? (bool)$opts['fill'] : true;

        // Vertical text tuning (match the style of the top tabs)
        $tabLift = isset($opts['tabLift']) ? (float)$opts['tabLift'] : 0.5;

        $tabWs = array();
        $visibleIdx = array();
        $totalW = 0.0;

        $count = is_array($items) ? count($items) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($items[$i]['action'])) continue;

            $title = isset($items[$i]['title']) ? (string)$items[$i]['title'] : '';
            $w = UMPanel::mlTabWidth($title, $tabTextsize, $tabPadLR, $tabMinW, $tabMaxW);

            $tabWs[] = $w;
            $visibleIdx[] = $i;

            $totalW += $w;
            if (count($tabWs) > 1) $totalW += $tabGap;
        }

        // Compute max available width BEFORE prepare() clamps it.
        $panelW = (float)$layout->geometry->panelWidth;
        $contentL = isset($opts['contentL']) ? (float)$opts['contentL'] : 1.2;
        $gap = isset($opts['gap']) ? (float)$opts['gap'] : 0.0;
        $contentR = isset($opts['contentR']) ? (float)$opts['contentR'] : 1.2;
        $submenuR = isset($opts['submenuR']) ? (float)$opts['submenuR'] : $contentR;

        $maxSubW = $panelW - $contentL - $gap - $submenuR;
        if ($maxSubW < 0) $maxSubW = 0;

        // Choose desired strip width:
        // - autoWidth: at least totalW (so no cropping)
        // - fill: expand to maxSubW if there's spare room
        if ($autoWidth) {
            $desiredW = $totalW;
            if ($fill && $maxSubW > $desiredW) $desiredW = $maxSubW;
            $opts['submenuW'] = $desiredW;
        }

        $p = self::prepare($login, $layout, $menuKey, $items, $opts);

        $panelH = isset($layout->geometry->panelHeight) ? (float)$layout->geometry->panelHeight : 0.0;
        $bottomMargin = isset($opts['bottomMargin']) ? (float)$opts['bottomMargin'] : 0.6;
        $y = isset($opts['bottomY']) ? (float)$opts['bottomY'] : (-$panelH + $bottomMargin + $p['rowH']);

        // --- DEBUG helpers for calibration ---
        $debug = isset($opts['debug']) ? (bool)$opts['debug'] : false;
        $debugBox = isset($opts['debugBox']) ? (bool)$opts['debugBox'] : false;
        $debugColorLine = isset($opts['debugColorLine']) ? (string)$opts['debugColorLine'] : 'F006';
        $debugColorBox  = isset($opts['debugColorBox']) ? (string)$opts['debugColorBox'] : '0F02';

        $xml = '';

        if ($debugBox) {
            $xml .= XmlTag::quad($p['submenuX'], $y, $p['submenuW'], $p['rowH'], $debugColorBox, null, array('z' => 0.10));
        }
        if ($debug) {
            $lineH = 0.12;
            $xml .= XmlTag::quad($p['submenuX'], $y, $p['submenuW'], $lineH, $debugColorLine, null, array('z' => 0.11));
        }

        // Scale tab widths to exactly fill p['submenuW'] (whether shrinking OR growing).
        $scale = 1.0;
        if ($totalW > 0 && $p['submenuW'] > 0) {
            $scale = $p['submenuW'] / $totalW;
        }

        $x = $p['submenuX'];
        $visCount = count($tabWs);

        // Match top-tabs vertical formula: y is the top of the quad strip.
        $tabTextY = $y - ($p['rowH'] / 1.5) + $tabLift;

        for ($k = 0; $k < $visCount; $k++) {
            $i = $visibleIdx[$k];

            $itActionName = (string)$items[$i]['action'];
            $isActive = ($p['activeAction'] === $itActionName);
            $itActionId = isset($p['mlAct'][$itActionName]) ? (int)$p['mlAct'][$itActionName] : 0;

            $w = $tabWs[$k] * $scale;
            if ($w < 0) $w = 0;

            $bg = $isActive ? '060D' : '010D';
            $xml .= XmlTag::quad($x, $y, $w, $p['rowH'], $bg, $itActionId, array('z' => 0.14));

            $title = isset($items[$i]['title']) ? $items[$i]['title'] : '';
            $centerX = $x + ($w / 2.0);

            // IMPORTANT: disable autonewline for tab labels
            $xml .= XmlTag::labelCenterCenter(
                $centerX,
                $tabTextY,
                $w,
                $p['rowH'],
                "\$fff\$o" . StringUtils::safeString($title),
                null,
                array('z' => 0.2, 'textsize' => $tabTextsize, 'autonewline' => 0)
            );

            $x += $w;
            if ($k < ($visCount - 1)) $x += $tabGap;
        }

        $contentW = $p['panelW'] - $p['contentL'] - $p['contentR'];
        if ($contentW < 0) $contentW = 0;

        return array(
            'xml' => $xml,
            'contentW' => $contentW,
            'submenuW' => $p['submenuW'],
            'stateKey' => $p['stateKey'],
            'activeAction' => $p['activeAction'],
        );
    }

    /**
     * Shared prep: geometry, clamping, activeAction resolution.
     * Returns an array of prepared values.
     */
    private static function prepare($login, Layout $layout, $menuKey, $items, $opts) {
        global $_players, $_ml_act;

        $panelW = (float)$layout->geometry->panelWidth;
        $topY   = (float)$layout->geometry->panelBodyTopY;

        $contentL = isset($opts['contentL']) ? (float)$opts['contentL'] : 1.2;
        $contentR = isset($opts['contentR']) ? (float)$opts['contentR'] : 1.2;
        $gap      = isset($opts['gap']) ? (float)$opts['gap'] : 0.0;

        $submenuW = isset($opts['submenuW']) ? (float)$opts['submenuW'] : 18.0;
        $rowH     = isset($opts['rowH']) ? (float)$opts['rowH'] : 2.8;
        $padX     = isset($opts['padX']) ? (float)$opts['padX'] : 0.7;
        $padY     = isset($opts['padY']) ? (float)$opts['padY'] : 0.8;

        $submenuR = isset($opts['submenuR']) ? (float)$opts['submenuR'] : $contentR;

        $maxSubW = $panelW - $contentL - $gap - $submenuR;
        if ($maxSubW < 0) $maxSubW = 0;
        if ($submenuW > $maxSubW) $submenuW = $maxSubW;

        $submenuX = $panelW - $submenuR - $submenuW;

        $contentW = $submenuX - $gap - $contentL;
        if ($contentW < 0) $contentW = 0;

        $stateKey = (string)$menuKey . UmPanelKeys::ACT_SUBTAB_IDENTIFIER;

        $defaultAction = '';
        if (isset($opts['defaultAction'])) {
            $defaultAction = (string)$opts['defaultAction'];
        } elseif (is_array($items) && isset($items[0]) && isset($items[0]['action'])) {
            $defaultAction = (string)$items[0]['action'];
        }

        $activeAction = isset($_players[$login]['ML'][$stateKey]) ? (string)$_players[$login]['ML'][$stateKey] : '';
        if ($activeAction === '') $activeAction = $defaultAction;

        $known = false;
        $count = is_array($items) ? count($items) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (isset($items[$i]['action']) && (string)$items[$i]['action'] === $activeAction) {
                $known = true;
                break;
            }
        }
        if (!$known) $activeAction = $defaultAction;

        $_players[$login]['ML'][$stateKey] = $activeAction;

        return array(
            'mlAct' => $_ml_act,
            'panelW' => $panelW,
            'topY' => $topY,
            'contentL' => $contentL,
            'contentR' => $contentR,
            'gap' => $gap,
            'submenuW' => $submenuW,
            'submenuX' => $submenuX,
            'rowH' => $rowH,
            'padX' => $padX,
            'padY' => $padY,
            'contentW' => $contentW,
            'stateKey' => $stateKey,
            'activeAction' => $activeAction,
            'count' => $count,
        );
    }

}