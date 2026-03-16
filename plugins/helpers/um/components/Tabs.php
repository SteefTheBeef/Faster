<?php

class Tabs {

    /**
     * @return array<int, array{key:string,title:string,action:string}>
     */
    public static function getTabs() {
        return array(
            array('title' => 'Semi-Final', 'action' => UmPanelKeys::ACT_TAB_SEMI_FINAL),
            array('title' => 'Qualification', 'action' => UmPanelKeys::ACT_TAB_QUALIFICATION),
            array('title' => 'Map Ratings', 'action' => UmPanelKeys::ACT_TAB_MAPS),
            array('title' => 'Information', 'action' => UmPanelKeys::ACT_TAB_RULES),
        );
    }

    /**
     * @param string $activeFromState action name (e.g. 'um.tab.players')
     * @param array  $tabs from self::getTabs()
     * @return string valid action name (fallbacks to first tab)
     */
    public static function getActiveTabAction($activeFromState, array $tabs) {
        $defaultAction = isset($tabs[0]) ? (string)$tabs[0]['action'] : UmPanelKeys::ACT_TAB_QUALIFICATION;

        $active = (string)$activeFromState;
        if ($active === '') $active = $defaultAction;

        // validate: only allow actions present in registry
        $ok = false;
        $count = count($tabs);
        for ($i = 0; $i < $count; $i++) {
            if ((string)$tabs[$i]['action'] === $active) {
                $ok = true;
                break;
            }
        }
        return $ok ? $active : $defaultAction;
    }

    /**
     * Resolve a human title from a tab action.
     *
     * @param string $tabAction
     * @param array  $tabs from self::getTabs()
     * @return string
     */
    public static function getTitleByAction($tabAction, array $tabs) {
        $tabAction = (string)$tabAction;
        $count = count($tabs);
        for ($i = 0; $i < $count; $i++) {
            if ((string)$tabs[$i]['action'] === $tabAction) {
                return (string)$tabs[$i]['title'];
            }
        }
        return '';
    }

    public static function render(Layout $layout, $activeTabAction, array $mlAct) {
        $tabH = 3;
        $tabGap = 0.0;
        $tabRightMargin = 1.2;
        $tabTextPrefix = '$fff$o';
        $tabLift = 0.5;
        $tabTextY = -($tabH / 1.5) + $tabLift;

        $tabs = self::getTabs();

        $activeTabAction = self::getActiveTabAction($activeTabAction, $tabs);

        $totalW = 0.0;
        $count = count($tabs);
        for ($i = 0; $i < $count; $i++) {
            $tabs[$i]['w'] = UMPanel::mlTabWidth($tabs[$i]['title'], 1.0, 1.8, 6.0, 26.0);
            $totalW += $tabs[$i]['w'];
            if ($i > 0) $totalW += $tabGap;
        }

        $tabsX = $layout->geometry->panelWidth - $tabRightMargin - $totalW;
        if ($tabsX < 0) $tabsX = 0;

        $tabsY = $tabH;

        $borderT = 0.12;
        $dividerT = 0.10;

        $inner = XmlTag::quad(0, 0, $totalW, $borderT, $layout->theme->borderColor);
        $inner .= XmlTag::quad(0, 0, $borderT, $tabH, $layout->theme->borderColor);

        $rightX = $totalW - $borderT;
        if ($rightX < 0) $rightX = 0;
        $inner .= XmlTag::quad($rightX, 0, $borderT, $tabH, $layout->theme->borderColor);

        $x = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $w = $tabs[$i]['w'];
            $isActive = ((string)$activeTabAction === (string)$tabs[$i]['action']);
            $bg = $isActive ? $layout->theme->tabActiveBackgroundColor : $layout->theme->tabBackgroundColor;

            $actName = $tabs[$i]['action'];
            $actId = isset($mlAct[$actName]) ? (int)$mlAct[$actName] : 0;

            $inner .= XmlTag::quad($x, 0, $w, $tabH, $bg, $actId);

            if ($i < ($count - 1)) {
                $divX = $x + $w - ($dividerT / 2.0);
                $inner .= XmlTag::quad($divX, 0, $dividerT, $tabH, $layout->theme->borderColor);
            }

            $centerX = $x + ($w / 2.0);
            $inner .= XmlTag::labelCenterCenter($centerX, $tabTextY, $w, $tabH, $tabTextPrefix . $tabs[$i]['title']);

            $x += $w + $tabGap;
        }

        return XmlTag::frame($tabsX, $tabsY, 0.30, $inner);
    }
}