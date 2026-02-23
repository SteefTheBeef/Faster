<?php

require_once __DIR__ . '/layout/Layout.php';
require_once __DIR__ . '/../utils/XmlTag.php';

class UmPanelRenderer
{
    /**
     * Read per-player ML UI state with a default.
     *
     * @param string $login
     * @param string $key   Example: 'um.panel.closed'
     * @param mixed  $default
     * @return mixed
     */
    private static function mlGet($login, $key, $default = null)
    {
        global $_players;

        if (!isset($_players[$login]) || !isset($_players[$login]['ML']) || !is_array($_players[$login]['ML'])) {
            return $default;
        }
        if (!array_key_exists($key, $_players[$login]['ML'])) {
            return $default;
        }

        return $_players[$login]['ML'][$key];
    }

    /**
     * Write per-player ML UI state (creates ML array if missing).
     *
     * @param string $login
     * @param string $key
     * @param mixed  $value
     * @return void
     */
    private static function mlSet($login, $key, $value)
    {
        global $_players;

        if (!isset($_players[$login])) {
            $_players[$login] = array();
        }
        if (!isset($_players[$login]['ML']) || !is_array($_players[$login]['ML'])) {
            $_players[$login]['ML'] = array();
        }

        $_players[$login]['ML'][$key] = $value;
    }

    public static function buildPanelXml($login, Layout $layout, array $players, $selectedRow, array $actionIds)
    {
        $isClosed = ((int)self::mlGet($login, 'um.panel.closed', 0) === 1);
        if ($isClosed) {
            return self::buildClosedToggleXml($layout);
        }
        $frameStart = '<frame posn="0 0 5">';
        $bordersXml = self::buildPanelBordersXml($layout);

        $listBuild = self::buildPlayerListXml($layout, $players, (int)$selectedRow, $actionIds);
        $xmlPlayers = $listBuild['xmlPlayers'];

        $selectedPlayer = null;
        if ($listBuild['selectedPlayerIndex'] !== null && isset($players[$listBuild['selectedPlayerIndex']])) {
            $selectedPlayer = $players[$listBuild['selectedPlayerIndex']];
        }

        $rightBuild = self::buildRightPanelXml($login, $layout, $selectedPlayer);
        $rightPanelXml =
            $rightBuild['panelFrameStart']
            . $rightBuild['tabsXml']
            . $rightBuild['closeXml']
            . $rightBuild['panelBgQuad']
            . $rightBuild['panelTitle']
            . $rightBuild['panelBody']
            . $rightBuild['panelFrameEnd'];
        return
            $frameStart
            . $bordersXml
            . $layout->markup->playerFrameStart
            . $xmlPlayers
            . $layout->markup->playerFrameEnd
            . $rightPanelXml
            . $layout->markup->frameEnd;
    }

    public static function handleAction($login, $action, $answer, array &$selectedRowByLogin, array &$selectedPlayerByLogin)
    {
        global $_players;
        // Panel close/open
        if ($action === 'um.panel.close') {
            self::mlSet($login, 'um.panel.closed', 1);
            return true;
        }
        if ($action === 'um.panel.open') {
            self::mlSet($login, 'um.panel.closed', 0);
            return true;
        }
        // Tabs
        if (strpos($action, 'um.tab.') === 0) {
            $parts = explode('.', $action);
            self::mlSet($login, 'um.tab', isset($parts[2]) ? $parts[2] : 'players');
            return true;
        }
        // Subtabs
        if (strpos($action, 'um.subtab.') === 0) {
            $parts = explode('.', $action);
            $tabKey = isset($parts[2]) ? $parts[2] : '';
            $subKey = isset($parts[3]) ? $parts[3] : '';
            if ($tabKey !== '' && $subKey !== '') {
                self::mlSet($login, 'um.subtab.' . $tabKey, $subKey);
            }
            return true;
        }
        // Paging
        if ($action === 'races.prev' || $action === 'races.next') {
            if (!isset($selectedPlayerByLogin[$login])) {
                return true; // handled, nothing to do
            }

            $pageCount = UMPanel::racesPageCount($selectedPlayerByLogin[$login]['Races']);
            $page = (int)self::mlGet($login, 'player.races.page', 0);

            if ($action === 'races.prev') $page--;
            if ($action === 'races.next') $page++;

            $page = UMPanel::clampInt($page, 0, $pageCount - 1);
            self::mlSet($login, 'player.races.page', $page);

            return true;
        }
        // Scoreboard row selection fallback: "<something>.<rowIndex>"
        $parts = explode('.', $action);
        $playerRow = isset($parts[1]) ? (int)$parts[1] : -1;
        $selectedRowByLogin[$login] = $playerRow;
        return true;
    }

    // ---------------- Rendering helpers ----------------

    private static function buildPanelBordersXml(Layout $layout)
    {
        $t = $layout->geometry->borderThickness;
        $c = $layout->theme->borderColor;
        $borderLeftX = $layout->geometry->borderLeftX;
        $borderHeight = $layout->geometry->borderHeight;
        $listFrameY = $layout->geometry->listFrameY;
        $borderTopWidth = $layout->geometry->borderOuterWidth;
        $borderTopY = $layout->geometry->borderTopY;
        $borderBottomY = $layout->geometry->borderBottomY;

        $left = XmlTag::quad($borderLeftX, $listFrameY, $t, $borderHeight, $c);
        $top = XmlTag::quad($borderLeftX, $borderTopY, $borderTopWidth, $t, $c);
        $bottom = XmlTag::quad($borderLeftX, $borderBottomY, $borderTopWidth, $t, $c);
        $right = XmlTag::quad($borderLeftX, $listFrameY, $t, $borderHeight, $c);

        return $left . $top . $bottom . $right;

        return $left . $top . $bottom . $right;
    }

    private static function buildPlayerListXml(Layout $layout, array $players, $selectedRow, array $actionIds)
    {
        $padX = 1.0;
        $padY = 1.5;
        $rows = (int)$layout->geometry->rowCount;
        $playerW = $layout->geometry->playerWidth;
        $playerH = $layout->geometry->playerHeight;
        $pointsW = 6.0;
        $pointsRightX = $playerW - $padX;
        $rowSpacing = 0.0;

        $xmlPlayers = '';
        $selectedPlayerIndex = null;

        for ($i = 0; $i < $rows; $i++) {
            $rowY = -$i * ($playerH + $rowSpacing);
            $name = isset($players[$i]) ? $players[$i]['NickNameWithColor'] : '';
            $points = isset($players[$i]) ? $players[$i]['Points'] : '';
            $bg = ($i === (int)$selectedRow) ? $layout->theme->cardSelectedBackgroundColor : $layout->theme->cardBackgroundColor;
            $actionId = isset($actionIds[$i]) ? (int)$actionIds[$i] : 0;

            if ($i === (int)$selectedRow && isset($players[$i])) {
                $selectedPlayerIndex = $i;
            }

            $xmlPlayers .= "<quad posn='0 {$rowY} 0' sizen='{$playerW} {$playerH}' halign='left' valign='top' bgcolor='{$bg}' action='{$actionId}'/>";
            $xmlPlayers .= "<label posn='{$padX} " . ($rowY - $padY) . " 0.2' sizen='" . ($playerW - 1.2) . " {$playerH}' halign='left' valign='center' textsize='1' text='\$fc0" . ($i + 1) . "'/>";
            $nameLeftX = $padX * 3.0;
            $nameW = ($pointsRightX - $pointsW) - $nameLeftX;
            $xmlPlayers .= "<label posn='{$nameLeftX} " . ($rowY - $padY) . " 0.2' sizen='{$nameW} {$playerH}' halign='left' valign='center' textsize='1' text='" . self::safeString($name) . "'/>";
            $xmlPlayers .= "<label posn='{$pointsRightX} " . ($rowY - $padY) . " 0.3' sizen='{$pointsW} {$playerH}' halign='right' valign='center' textsize='1' text='" . self::safeString($points) . "'/>";
        }

        return array(
            'xmlPlayers' => $xmlPlayers,
            'selectedPlayerIndex' => $selectedPlayerIndex,
        );
    }

    private static function buildClosedToggleXml(Layout $layout)
    {
        global $_ml_act;

        $x = $layout->geometry->closeButtonX;
        $y = $layout->geometry->closeButtonY;
        $s = $layout->geometry->closeButtonSize;

        $actId = isset($_ml_act['um.panel.open']) ? (int)$_ml_act['um.panel.open'] : 0;

        $xml  = "<frame posn='{$x} {$y} 5' halign='left' valign='top'>";
        $xml .= XmlTag::quadIcon64(0, 0, $s, 'Check', $actId);
        $xml .= "</frame>";

        return $xml;
    }

    private static function buildRightPanelXml($login, Layout $layout, $selectedPlayer)
    {
        global $_ml_act;
        $panelW = $layout->geometry->panelWidth;
        $panelH = $layout->geometry->panelHeight;
        $tabsXml = self::buildTabsXml($login, $layout);
        $closeSize = 2.2;
        $closeMarginR = 0.25;
        $closeY = -0.5;
        $closeX = $panelW - $closeMarginR - $closeSize;
        if ($closeX < 0) $closeX = 0;
        $closeActId = isset($_ml_act['um.panel.close']) ? (int)$_ml_act['um.panel.close'] : 0;
        $closeXml = "<quad posn='{$closeX} {$closeY} 0.45' sizen='{$closeSize} {$closeSize}' style='Icons64x64_1' substyle='Circle' action='{$closeActId}'/>";
        $closeXml .= "<quad posn='{$closeX} {$closeY} 0.46' sizen='{$closeSize} {$closeSize}' style='Icons64x64_1' substyle='Close' action='{$closeActId}'/>";
        $panelBgQuad = "<quad posn='0 0 0' sizen='{$panelW} {$panelH}' halign='left' valign='top' bgcolor='{$layout->theme->panelBackgroundColor}'/>";

        $activeTab = (string)self::mlGet($login, 'um.tab', 'players');
        if ($activeTab === '') $activeTab = 'players';

        $selectedPlayerName = (is_array($selectedPlayer) && isset($selectedPlayer['NickNameWithColor'])) ? $selectedPlayer['NickNameWithColor'] : '';
        $titleText = ($activeTab === 'players') ? $selectedPlayerName : ucwords($activeTab);
        $font = ($activeTab === 'players') ? '' : $layout->theme->headerFontStyle;
        $panelTitle = "<label posn='1 -1 0.2' sizen='" . ($panelW - 2) . " 3' halign='left' valign='top' textsize='2' text='{$font}" . self::safeString($titleText) . "'/>";
        $panelBody = self::buildRightPanelBodyXml($login, $activeTab, $selectedPlayer, $layout);
        return array(
            'panelFrameStart' => $layout->markup->panelFrameStart,
            'panelFrameEnd' => $layout->markup->panelFrameEnd,
            'tabsXml' => $tabsXml,
            'closeXml' => $closeXml,
            'panelBgQuad' => $panelBgQuad,
            'panelTitle' => $panelTitle,
            'panelBody' => $panelBody,
        );
    }

    private static function buildRightPanelBodyXml($login, $activeTab, $selectedPlayer, Layout $layout)
    {
        global $umConfig;
        switch ($activeTab) {
            case 'schedule':
                return SchedulePanelBuilder::schedule($layout, $umConfig);
            case 'rules':
                return RulesPanelBuilder::build($login, $layout, $umConfig);
            case 'information':
                return InformationPanelBuilder::getInformationPanel($layout);
            case 'stints':
                return '';
            case 'players':
            default:
                return self::buildPlayerRacesPanelXml($login, $selectedPlayer, $layout);
        }
    }

    private static function buildPlayerRacesPanelXml($login, $selectedPlayer, Layout $layout)
    {
        if (!is_array($selectedPlayer) || !isset($selectedPlayer['Races']) || !is_array($selectedPlayer['Races']) || count($selectedPlayer['Races']) < 1) {
            return UMPanel::textLabel($layout, 'Select a player on the left...');
        }
        return self::buildRacesTableXml($login, $selectedPlayer['Races'], $layout);
    }

    private static function buildTabsXml($login, Layout $layout)
    {
        global $_ml_act;
        $tabH = 3;
        $tabGap = 0.0;
        $tabRightMargin = 1.2;
        $tabTextPrefix = '$fff$o';
        $tabLift = 0.5;
        $tabTextY = -($tabH / 1.5) + $tabLift;
        $tabs = array(
            array('key' => 'players', 'title' => 'Players', 'action' => 'um.tab.players'),
            array('key' => 'stints', 'title' => 'Stints', 'action' => 'um.tab.stints'),
            array('key' => 'prize', 'title' => 'Prize Pool', 'action' => 'um.tab.prize'),
            array('key' => 'schedule', 'title' => 'Schedule', 'action' => 'um.tab.schedule'),
            array('key' => 'rules', 'title' => 'Rules', 'action' => 'um.tab.rules'),
            array('key' => 'information', 'title' => 'Information', 'action' => 'um.tab.information'),
        );

        $activeTab = (string)self::mlGet($login, 'um.tab', $tabs[0]['key']);
        if ($activeTab === '') $activeTab = $tabs[0]['key'];

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
        $xml = "<frame posn='{$tabsX} {$tabsY} 0.30' halign='left' valign='top'>";
        $borderT = 0.12;
        $dividerT = 0.10;
        $xml .= "<quad posn='0 0 0.02' sizen='{$totalW} {$borderT}' halign='left' valign='top' bgcolor='{$layout->theme->borderColor}'/>";
        $xml .= "<quad posn='0 0 0.02' sizen='{$borderT} {$tabH}' halign='left' valign='top' bgcolor='{$layout->theme->borderColor}'/>";
        $rightX = $totalW - $borderT;
        if ($rightX < 0) $rightX = 0;
        $xml .= "<quad posn='{$rightX} 0 0.02' sizen='{$borderT} {$tabH}' halign='left' valign='top' bgcolor='{$layout->theme->borderColor}'/>";
        $x = 0.0;
        for ($i = 0; $i < $count; $i++) {
            $w = $tabs[$i]['w'];
            $isActive = ($activeTab === $tabs[$i]['key']);
            $bg = $isActive ? $layout->theme->tabActiveBackgroundColor : $layout->theme->tabBackgroundColor;
            $actName = $tabs[$i]['action'];
            $actId = isset($_ml_act[$actName]) ? (int)$_ml_act[$actName] : 0;
            $xml .= "<quad posn='{$x} 0 0' sizen='{$w} {$tabH}' halign='left' valign='top' bgcolor='{$bg}' action='{$actId}'/>";
            if ($i < ($count - 1)) {
                $divX = $x + $w - ($dividerT / 2.0);
                $xml .= "<quad posn='{$divX} 0 0.015' sizen='{$dividerT} {$tabH}' halign='left' valign='top' bgcolor='{$layout->theme->borderColor}'/>";
            }
            $centerX = $x + ($w / 2.0);
            $xml .= "<label posn='{$centerX} {$tabTextY} 0.1' sizen='{$w} {$tabH}' halign='center' valign='center' textsize='1' text='{$tabTextPrefix}{$tabs[$i]['title']}'/>";
            $x += $w + $tabGap;
        }
        $xml .= "</frame>";
        return $xml;
    }

    private static function buildRacesTableXml($login, $races, Layout $layout)
    {
        global $_ml_act;
        $panelW = $layout->geometry->panelWidth;
        $panelH = $layout->geometry->panelHeight;
        $topY = $layout->geometry->panelBodyTopY;
        $contentL = 1.2;
        $contentR = 1.2;
        $gutter = 0.5;
        $gutterAfterIdx = 0.9;
        $idxW = 3.0;
        $usableW = $panelW - $contentL - $contentR - (3 * $gutter) - $gutterAfterIdx;
        if ($usableW < 0) $usableW = 0;
        if ($idxW > $usableW) $idxW = $usableW;
        $otherW = $usableW - $idxW;
        if ($otherW < 0) $otherW = 0;
        $colW = $otherW / 4.0;
        $idxPadL = 0.4;
        $timePadR = $idxPadL;
        $xIdxLeft = $contentL + $idxPadL;
        $xEnv = $contentL + $idxW + $gutterAfterIdx;
        $xRank = $xEnv + $colW + $gutter;
        $xPts = $xRank + $colW + $gutter;
        $xTimeRight = $panelW - $contentR - $timePadR;
        $tableX = $contentL;
        $tableW = $panelW - $contentL - $contentR;
        if ($tableW < 0) $tableW = 0;
        $rowH = 2.4;
        $headerY = $topY;

        $page = (int)self::mlGet($login, 'player.races.page', 0);
        $pageCount = UMPanel::racesPageCount($races);
        $page = UMPanel::clampInt($page, 0, $pageCount - 1);
        self::mlSet($login, 'player.races.page', $page);

        $racesToShow = is_array($races) ? UMPanel::racesSliceForPage($races, $page) : array();
        $xml = '';
        $headerFont = '$cf0$o';
        $xml .= "<quad posn='{$tableX} {$headerY} 0.15' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='0006'/>";
        $xml .= "<label posn='{$xIdxLeft} " . ($headerY - 0.6) . " 0.2' sizen='" . ($idxW - $idxPadL) . " {$rowH}' halign='left' valign='top' textsize='1' text='{$headerFont}#'/>";
        $xml .= "<label posn='{$xEnv} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='left'  valign='top' textsize='1' text='{$headerFont}Environment'/>";
        $xml .= "<label posn='{$xRank} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Rank'/>";
        $xml .= "<label posn='{$xPts} " . ($headerY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Points'/>";
        $xml .= "<label posn='{$xTimeRight} " . ($headerY - 0.6) . " 0.2' sizen='" . ($colW - $timePadR) . " {$rowH}' halign='right' valign='top' textsize='1' text='{$headerFont}Time'/>";
        $i = 0;
        foreach ($racesToShow as $race) {
            $rowY = $headerY - (($i + 1) * $rowH);
            $raceIdx = isset($race['RaceIndex']) ? (string)(((int)$race['RaceIndex']) + 1) : (string)($i + 1);
            $env = '';
            if (isset($race['RaceInfo']) && is_array($race['RaceInfo']) && isset($race['RaceInfo']['Environment'])) {
                $env = $race['RaceInfo']['Environment'];
            }
            $rank = isset($race['Rank']) ? (string)$race['Rank'] : '';
            $time = '';
            if (isset($race['Score']) && is_array($race['Score'])) {
                if (isset($race['Score']['Time']) && $race['Score']['Time'] !== '') {
                    $time = $race['Score']['Time'];
                } elseif (isset($race['Score']['RaceTime']) && $race['Score']['RaceTime'] !== '') {
                    $time = $race['Score']['RaceTime'];
                }
            }
            $pts = isset($race['AwardedPoints']) ? (string)$race['AwardedPoints'] : '';
            $enviFont = '$390$o';
            $otherFont = ((int)$rank > 3) ? '$fff$o' : '$fc0$o';
            $bg = (($i % 2) === 0) ? '0003' : '0000';
            $xml .= "<quad posn='{$tableX} {$rowY} 0.10' sizen='{$tableW} {$rowH}' halign='left' valign='top' bgcolor='{$bg}'/>";
            $xml .= "<label posn='{$xIdxLeft} " . ($rowY - 0.6) . " 0.2' sizen='" . ($idxW - $idxPadL) . " {$rowH}' halign='left' valign='top' textsize='1' text='{$otherFont}" . self::safeString($raceIdx) . "'/>";
            $xml .= "<label posn='{$xEnv} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='left'  valign='top' textsize='1' text='{$enviFont}" . self::safeString($env) . "'/>";
            $xml .= "<label posn='{$xRank} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}' halign='right' valign='top' textsize='1' text='{$otherFont}" . self::safeString($rank) . "'/>";
            $xml .= "<label posn='{$xPts} " . ($rowY - 0.6) . " 0.2' sizen='{$colW} {$rowH}'  halign='right' valign='top' textsize='1' text='{$otherFont}" . self::safeString($pts) . "'/>";
            $xml .= "<label posn='{$xTimeRight} " . ($rowY - 0.6) . " 0.2' sizen='" . ($colW - $timePadR) . " {$rowH}' halign='right' valign='top' textsize='1' text='{$otherFont}" . self::safeString($time) . "'/>";
            $i++;
        }

        if (is_array($races) && count($races) > RACES_PER_PAGE) {
            $pageCount = UMPanel::racesPageCount($races);
            $page = (int)self::mlGet($login, 'player.races.page', 0);

            $canPrev = ($page > 0);
            $canNext = ($page < $pageCount - 1);
            $pagerY = $headerY - (($i + 1) * $rowH) - 1.2;
            $labelW = 2.0;
            $gap = 0.25;
            $nextX = $tableW - 1.6;
            $prevX = $nextX - 1.6 - $gap - $labelW - $gap - 1.6;
            $prevCenterX = $prevX + (1.6 / 2.0);
            $nextCenterX = $nextX + (1.6 / 2.0);
            $midX = ($prevCenterX + $nextCenterX) / 2.0;
            $midY = -0.8;
            $xml .= "<frame posn='{$tableX} {$pagerY} 0.2' halign='left' valign='top'>"
                . "<quad sizen='1.6 1.6' posn='{$prevX} 0 0' style='Icons64x64_1' substyle='ArrowPrev'"
                . ($canPrev ? " action='{$_ml_act['races.prev']}'" : "")
                . "/>"
                . "<label sizen='{$labelW} 1.6' posn='{$midX} {$midY} 0' textsize='1' valign='center' halign='center'"
                . " text='\$aaa" . ($page + 1) . "/" . $pageCount . "'/>"
                . "<quad sizen='1.6 1.6' posn='{$nextX} 0 0' style='Icons64x64_1' substyle='ArrowNext'"
                . ($canNext ? " action='{$_ml_act['races.next']}'" : "")
                . "/>"
                . "</frame>";
        }

        return $xml;
    }

    private static function safeString($str)
    {
        $str = (string)$str;
        $str = str_replace(array("\r", "\n", "\t"), ' ', $str);
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
