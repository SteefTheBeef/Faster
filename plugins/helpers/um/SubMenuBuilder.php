<?php
require_once 'layout/Layout.php';
require_once __DIR__ . '/../utils/StringUtils.php';
require_once __DIR__ . '/UmPanelKeys.php';

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

        // --- Active selection (robust default + validation) ---
        $stateKey = (string)$menuKey . UmPanelKeys::ACT_SUBTAB_IDENTIFIER;

        $defaultAction = '';
        if (isset($opts['defaultAction'])) {
            $defaultAction = (string)$opts['defaultAction'];
        } elseif (is_array($items) && isset($items[0]) && isset($items[0]['action'])) {
            $defaultAction = (string)$items[0]['action'];
        }

        $activeAction = isset($_players[$login]['ML'][$stateKey]) ? (string)$_players[$login]['ML'][$stateKey] : '';
        if ($activeAction === '') {
            $activeAction = $defaultAction;
        }

        // Validate stored value: only allow actions present in $items
        $known = false;
        $count = is_array($items) ? count($items) : 0;
        for ($i = 0; $i < $count; $i++) {
            if (isset($items[$i]['action']) && (string)$items[$i]['action'] === $activeAction) {
                $known = true;
                break;
            }
        }
        if (!$known) {
            $activeAction = $defaultAction;
        }

        // Persist resolved value (so highlight is stable and state is self-healing)
        $_players[$login]['ML'][$stateKey] = $activeAction;

        $xml = '';

        // Items
        $y = $topY - 3.2;
        for ($i = 0; $i < $count; $i++) {
            if (!isset($items[$i]['action'])) {
                continue;
            }

            $itActionName = (string)$items[$i]['action'];
            $isActive = ($activeAction === $itActionName);

            $itActionId = isset($_ml_act[$itActionName]) ? (int)$_ml_act[$itActionName] : 0;

            $bg = $isActive ? '060D' : '010D';
            $xml .= XmlTag::quad($submenuX, $y, $submenuW, $rowH, $bg, $itActionId, array('z' => 0.14));

            // Center text vertically in the row
            $textY = $y - ($rowH / 2.0);
            $title = isset($items[$i]['title']) ? $items[$i]['title'] : '';
            $xml .= XmlTag::labelCenterLeft(
                $submenuX + $padX,
                $textY,
                $submenuW - 2 * $padX,
                $rowH,
                "\$fff\$o" . StringUtils::safeString($title),
                null,
                array('z' => 0.2)
            );

            $y -= ($rowH);
        }

        return array(
            'xml' => $xml,
            'contentW' => $contentW,
            'submenuW' => $submenuW,
            'stateKey' => $stateKey,
            'activeAction' => $activeAction,
        );
    }
}