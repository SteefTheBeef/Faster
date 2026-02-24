<?php

require_once __DIR__ . '/layout/Layout.php';
require_once __DIR__ . '/../utils/XmlTag.php';

class UmPanelRenderer {
    /**
     * Read per-player ML UI state with a default.
     *
     * @param string $login
     * @param string $key Example: 'um.panel.closed'
     * @param mixed $default
     * @return mixed
     */
    private static function mlGet($login, $key, $default = null) {
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
     * @param mixed $value
     * @return void
     */
    private static function mlSet($login, $key, $value) {
        global $_players;

        if (!isset($_players[$login])) {
            $_players[$login] = array();
        }
        if (!isset($_players[$login]['ML']) || !is_array($_players[$login]['ML'])) {
            $_players[$login]['ML'] = array();
        }

        $_players[$login]['ML'][$key] = $value;
    }

    public static function buildPanelXml($login, Layout $layout, array $players, $selectedRow, array $actionIds) {
        $isClosed = ((int)self::mlGet($login, 'um.panel.closed', 0) === 1);
        if ($isClosed) {
            return self::buildClosedToggleXml($layout);
        }

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

        return XmlTag::frame(0, 0, 5,
            $bordersXml
            . $layout->markup->playerFrameStart
            . $xmlPlayers
            . $layout->markup->playerFrameEnd
            . $rightPanelXml
        );
    }

    public static function handleAction($login, $action, $answer, array &$selectedRowByLogin, array &$selectedPlayerByLogin) {
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

    private static function buildPanelBordersXml(Layout $layout) {
        $t = $layout->geometry->borderThickness;
        $c = $layout->theme->borderColor;

        $borderLeftX = $layout->geometry->borderLeftX;
        $borderRightX = isset($layout->geometry->borderRightX)
            ? $layout->geometry->borderRightX
            : ($layout->geometry->borderLeftX + $layout->geometry->borderOuterWidth - $t);

        $borderHeight = $layout->geometry->borderHeight;
        $listFrameY = $layout->geometry->listFrameY;

        $borderTopWidth = $layout->geometry->borderOuterWidth;
        $borderTopY = $layout->geometry->borderTopY;
        $borderBottomY = $layout->geometry->borderBottomY;

        $xml = XmlTag::quadBorder($borderLeftX, $listFrameY, $t, $borderHeight, $c); // left
        $xml .= XmlTag::quadBorder($borderLeftX, $borderTopY, $borderTopWidth, $t, $c); // top
        $xml .= XmlTag::quadBorder($borderLeftX, $borderBottomY, $borderTopWidth, $t, $c); // bottom
        $xml .= XmlTag::quadBorder($borderRightX, $listFrameY, $t, $borderHeight, $c); // right

        return $xml;
    }

    private static function buildPlayerListXml(Layout $layout, array $players, $selectedRow, array $actionIds) {
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

            $xmlPlayers .= XmlTag::quad(0, $rowY, $playerW, $playerH, $bg, $actionId);
            $xmlPlayers .= XmlTag::labelCenterLeft($padX, $rowY - $padY, $playerW - 1.2, $playerH, "\$fc0" . ($i + 1));

            $nameLeftX = $padX * 3.0;
            $nameW = ($pointsRightX - $pointsW) - $nameLeftX;

            $xmlPlayers .= XmlTag::labelCenterLeft($nameLeftX, $rowY - $padY, $nameW, $playerH, $name);
            $xmlPlayers .= XmlTag::labelCenterRight($pointsRightX, $rowY - $padY, $pointsW, $playerH, $points);
        }

        return array(
            'xmlPlayers' => $xmlPlayers,
            'selectedPlayerIndex' => $selectedPlayerIndex,
        );
    }

    private static function buildClosedToggleXml(Layout $layout) {
        global $_ml_act;

        $x = $layout->geometry->closeButtonX;
        $y = $layout->geometry->closeButtonY;
        $s = $layout->geometry->closeButtonSize;

        $actId = isset($_ml_act['um.panel.open']) ? (int)$_ml_act['um.panel.open'] : 0;

        return XmlTag::frame($x, $y, 5, XmlTag::quadIcon64(0, 0, $s, 'Check', $actId));
    }

    private static function buildRightPanelXml($login, Layout $layout, $selectedPlayer) {
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

        $closeXml =
            XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Circle', $closeActId, array('z' => 0.45))
            . XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Close', $closeActId, array('z' => 0.46));

        $panelBgQuad = XmlTag::quad(0, 0, $panelW, $panelH, $layout->theme->panelBackgroundColor);

        $activeTab = (string)self::mlGet($login, 'um.tab', 'players');
        if ($activeTab === '') $activeTab = 'players';

        $selectedPlayerName = (is_array($selectedPlayer) && isset($selectedPlayer['NickNameWithColor'])) ? $selectedPlayer['NickNameWithColor'] : '';
        $titleText = ($activeTab === 'players') ? $selectedPlayerName : ucwords($activeTab);
        $font = ($activeTab === 'players') ? '' : $layout->theme->headerFontStyle;

        $panelTitle = XmlTag::label(1, -1, $panelW - 2, 3, $font . $titleText, null, array('textsize' => 2));

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

    private static function buildRightPanelBodyXml($login, $activeTab, $selectedPlayer, Layout $layout) {
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

    private static function buildPlayerRacesPanelXml($login, $selectedPlayer, Layout $layout) {
        if (!is_array($selectedPlayer) || !isset($selectedPlayer['Races']) || !is_array($selectedPlayer['Races']) || count($selectedPlayer['Races']) < 1) {
            return UMPanel::textLabel($layout, 'Select a player on the left...');
        }
        return self::buildRacesTableXml($login, $selectedPlayer['Races'], $layout);
    }

    private static function buildTabsXml($login, Layout $layout) {
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
            $isActive = ($activeTab === $tabs[$i]['key']);
            $bg = $isActive ? $layout->theme->tabActiveBackgroundColor : $layout->theme->tabBackgroundColor;

            $actName = $tabs[$i]['action'];
            $actId = isset($_ml_act[$actName]) ? (int)$_ml_act[$actName] : 0;

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

    private static function buildRacesTableXml($login, $races, Layout $layout) {
        global $_ml_act;

        $panelW = $layout->geometry->panelWidth;
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

        $xml .= XmlTag::quad($tableX, $headerY, $tableW, $rowH, '0006');
        $xml .= XmlTag::label($xIdxLeft, $headerY - 0.6, $idxW - $idxPadL, $rowH, $headerFont . '#');
        $xml .= XmlTag::label($xEnv, $headerY - 0.6, $colW, $rowH, $headerFont . 'Environment');
        $xml .= XmlTag::labelRight($xRank, $headerY - 0.6, $colW, $rowH, $headerFont . 'Rank');
        $xml .= XmlTag::labelRight($xPts, $headerY - 0.6, $colW, $rowH, $headerFont . 'Points');
        $xml .= XmlTag::labelRight($xTimeRight, $headerY - 0.6, $colW - $timePadR, $rowH, $headerFont . 'Time');

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

            $xml .= XmlTag::quad($tableX, $rowY, $tableW, $rowH, $bg);
            $xml .= XmlTag::label($xIdxLeft, $rowY - 0.6, $idxW - $idxPadL, $rowH, $otherFont . $raceIdx);
            $xml .= XmlTag::label($xEnv, $rowY - 0.6, $colW, $rowH, $enviFont . $env);
            $xml .= XmlTag::labelRight($xRank, $rowY - 0.6, $colW, $rowH, $otherFont . $rank);
            $xml .= XmlTag::labelRight($xPts, $rowY - 0.6, $colW, $rowH, $otherFont . $pts);
            $xml .= XmlTag::labelRight($xTimeRight, $rowY - 0.6, $colW - $timePadR, $rowH, $otherFont . $time);

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

            $prevAct = ($canPrev && isset($_ml_act['races.prev'])) ? (int)$_ml_act['races.prev'] : null;
            $nextAct = ($canNext && isset($_ml_act['races.next'])) ? (int)$_ml_act['races.next'] : null;

            $pagerInner = XmlTag::quadIcon64($prevX, 0, 1.6, 'ArrowPrev', $prevAct);
            $pagerInner .= XmlTag::labelCenterCenter($midX, $midY, $labelW, 1.6, "\$aaa" . ($page + 1) . "/" . $pageCount);
            $pagerInner .= XmlTag::quadIcon64($nextX, 0, 1.6, 'ArrowNext', $nextAct);

            $xml .= XmlTag::frame($tableX, $pagerY, 0.2, $pagerInner);
        }

        return $xml;
    }
}
