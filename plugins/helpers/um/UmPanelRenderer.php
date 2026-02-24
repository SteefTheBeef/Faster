<?php

require_once __DIR__ . '/layout/Layout.php';
require_once __DIR__ . '/../utils/XmlTag.php';
require_once __DIR__ . '/UmPanelTabs.php';
require_once __DIR__ . '/UmPanelKeys.php';

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

    public static function buildPanelXml($login, Layout $layout, array $players, $selectedRow, $selectedPlayerForLogin, array $actionIds, array $mlAct, $umConfig) {
        $isClosed = ((int)self::mlGet($login, UmPanelKeys::ML_PANEL_CLOSED, 0) === 1);
        if ($isClosed) {
            return self::buildClosedToggleXml($layout, $mlAct);
        }

        $bordersXml = self::buildPanelBordersXml($layout);

        $listBuild = self::buildPlayerListXml($layout, $players, (int)$selectedRow, $actionIds);
        $xmlPlayers = $listBuild['xmlPlayers'];

        $rightBuild = self::buildRightPanelXml($login, $layout, $selectedPlayerForLogin, $mlAct, $umConfig);
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

    public static function handleAction($login, $action, $answer, array &$selectedRowByLogin, array &$selectedPlayer = null) {
        global $_players, $umScoreBoardPlayers;

        // Panel close/open
        if ($action === UmPanelKeys::ACT_PANEL_CLOSE) {
            self::mlSet($login, UmPanelKeys::ML_PANEL_CLOSED, 1);
            return true;
        }

        if ($action === UmPanelKeys::ACT_PANEL_OPEN) {
            self::mlSet($login, UmPanelKeys::ML_PANEL_CLOSED, 0);
            return true;
        }

        // Tabs: store the tab *action* as state (single identifier)
        if (strpos($action, UmPanelKeys::ACT_TAB_PREFIX) === 0) {
            $tabs = UmPanelTabs::getTabs();

            $known = false;
            $count = count($tabs);
            for ($i = 0; $i < $count; $i++) {
                if (isset($tabs[$i]['action']) && (string)$tabs[$i]['action'] === (string)$action) {
                    $known = true;
                    break;
                }
            }

            if ($known) {
                self::mlSet($login, UmPanelKeys::ML_TAB, (string)$action);
                return true;
            }

            // Unknown um.tab.* action => don't write invalid state
            $defaultAction = isset($tabs[0]) ? (string)$tabs[0]['action'] : UmPanelKeys::ACT_TAB_PLAYERS;
            self::mlSet($login, UmPanelKeys::ML_TAB, $defaultAction);
            return false;
        }

        // Subtabs: "um.subtab.<tabKey>.<subKey>"
        if (strpos($action, UmPanelKeys::ACT_SUBTAB_PREFIX) === 0) {
            $rest = substr($action, strlen(UmPanelKeys::ACT_SUBTAB_PREFIX));
            $parts = explode('.', (string)$rest, 2);
            $tabKey = isset($parts[0]) ? $parts[0] : '';
            $subKey = isset($parts[1]) ? $parts[1] : '';
            if ($tabKey !== '' && $subKey !== '') {
                self::mlSet($login, UmPanelKeys::mlSubtabKey($tabKey), $subKey);
            }
            return true;
        }

        // Paging
        if ($action === UmPanelKeys::ACT_RACES_PREV || $action === UmPanelKeys::ACT_RACES_NEXT) {
            if (!isset($selectedPlayer[$login])) {
                return true; // known action, nothing to do
            }

            $pageCount = UMPanel::racesPageCount($selectedPlayer[$login]['Races']);
            $page = (int)self::mlGet($login, UmPanelKeys::ML_RACES_PAGE, 0);

            if ($action === UmPanelKeys::ACT_RACES_PREV) $page--;
            if ($action === UmPanelKeys::ACT_RACES_NEXT) $page++;

            $page = UMPanel::clampInt($page, 0, $pageCount - 1);
            self::mlSet($login, UmPanelKeys::ML_RACES_PAGE, $page);
            return true;
        }

        // Scoreboard row selection (explicitly accepted):
        // "umScoreBoardPlayerActions.<rowIndex>"
        $rowPrefix = UmPanelKeys::ACT_SCOREBOARD_ROW_PREFIX;
        if (strpos($action, $rowPrefix) === 0) {

            $rowStr = substr($action, strlen($rowPrefix));

            // Validate: digits only (avoid accidental "abc", "1foo", etc.)
            if ($rowStr !== '' && ctype_digit($rowStr)) {
                $row = (int)$rowStr;

                if ($row < 0) $row = 0;

                $selectedRowByLogin[$login] = $row;
                $selectedPlayer[$login] = isset($umScoreBoardPlayers[$row]) ? $umScoreBoardPlayers[$row] : null;
                return true;
            }

            return false;
        }

        return false;
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

    private static function buildClosedToggleXml(Layout $layout, array $mlAct) {
        $x = $layout->geometry->closeButtonX;
        $y = $layout->geometry->closeButtonY;
        $s = $layout->geometry->closeButtonSize;

        $actId = isset($mlAct[UmPanelKeys::ACT_PANEL_OPEN]) ? (int)$mlAct[UmPanelKeys::ACT_PANEL_OPEN] : 0;

        return XmlTag::frame($x, $y, 5, XmlTag::quadIcon64(0, 0, $s, 'Check', $actId));
    }

    private static function buildRightPanelXml($login, Layout $layout, $selectedPlayerForLogin, array $mlAct, $umConfig) {
        $panelW = $layout->geometry->panelWidth;
        $panelH = $layout->geometry->panelHeight;

        $activeTabRaw = (string)self::mlGet($login, UmPanelKeys::ML_TAB, 'players');

        $tabs = UmPanelTabs::getTabs();
        $activeTabAction = UmPanelTabs::getActiveTabAction($activeTabRaw, $tabs);

        $tabsXml = UmPanelTabs::buildTabsXml($layout, $activeTabAction, $mlAct);

        $closeSize = 2.2;
        $closeMarginR = 0.25;
        $closeY = -0.5;
        $closeX = $panelW - $closeMarginR - $closeSize;
        if ($closeX < 0) $closeX = 0;

        $closeActId = isset($mlAct[UmPanelKeys::ACT_PANEL_CLOSE]) ? (int)$mlAct[UmPanelKeys::ACT_PANEL_CLOSE] : 0;

        $closeXml =
            XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Circle', $closeActId, array('z' => 0.45))
            . XmlTag::quadIcon64($closeX, $closeY, $closeSize, 'Close', $closeActId, array('z' => 0.46));

        $panelBgQuad = XmlTag::quad(0, 0, $panelW, $panelH, $layout->theme->panelBackgroundColor);

        $selectedPlayerName = (is_array($selectedPlayerForLogin) && isset($selectedPlayerForLogin['NickNameWithColor'])) ? $selectedPlayerForLogin['NickNameWithColor'] : '';
        $titleText = ($activeTabAction === UmPanelKeys::ACT_TAB_PLAYERS)
            ? $selectedPlayerName
            : UmPanelTabs::getTitleByAction($activeTabAction, $tabs);

        $font = ($activeTabAction === UmPanelKeys::ACT_TAB_PLAYERS) ? '' : $layout->theme->headerFontStyle;

        $panelTitle = XmlTag::label(1, -1, $panelW - 2, 3, $font . $titleText, null, array('textsize' => 2));

        $panelBody = self::buildRightPanelBodyXml($login, $activeTabAction, $selectedPlayerForLogin, $layout, $umConfig, $mlAct);

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

    private static function buildRightPanelBodyXml($login, $activeTabAction, $selectedPlayer, Layout $layout, $umConfig, array $mlAct) {
        switch ((string)$activeTabAction) {
            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return SchedulePanelBuilder::schedule($layout, $umConfig);
            case UmPanelKeys::ACT_TAB_RULES:
                return RulesPanelBuilder::build($login, $layout, $umConfig);
            case UmPanelKeys::ACT_TAB_INFORMATION:
                return InformationPanelBuilder::getInformationPanel($layout);
            case UmPanelKeys::ACT_TAB_STINTS:
                return '';
            case UmPanelKeys::ACT_TAB_PRIZE:
                return '';
            case UmPanelKeys::ACT_TAB_PLAYERS:
            default:
                return self::buildPlayerRacesPanelXml($login, $selectedPlayer, $layout, $mlAct);
        }
    }

    private static function buildPlayerRacesPanelXml($login, $selectedPlayer, Layout $layout, array $mlAct) {
        if (!is_array($selectedPlayer) || !isset($selectedPlayer['Races']) || !is_array($selectedPlayer['Races']) || count($selectedPlayer['Races']) < 1) {
            return UMPanel::textLabel($layout, 'Select a player on the left...');
        }
        return self::buildRacesTableXml($login, $selectedPlayer['Races'], $layout, $mlAct);
    }

    private static function buildRacesTableXml($login, $races, Layout $layout, array $mlAct) {
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

        $page = (int)self::mlGet($login, UmPanelKeys::ML_RACES_PAGE, 0);
        $pageCount = UMPanel::racesPageCount($races);
        $page = UMPanel::clampInt($page, 0, $pageCount - 1);
        self::mlSet($login, UmPanelKeys::ML_RACES_PAGE, $page);

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

            $prevAct = ($canPrev && isset($mlAct[UmPanelKeys::ACT_RACES_PREV])) ? (int)$mlAct[UmPanelKeys::ACT_RACES_PREV] : null;
            $nextAct = ($canNext && isset($mlAct[UmPanelKeys::ACT_RACES_NEXT])) ? (int)$mlAct[UmPanelKeys::ACT_RACES_NEXT] : null;


            $pagerInner = XmlTag::quadIcon64($prevX, 0, 1.6, 'ArrowPrev', $prevAct);
            $pagerInner .= XmlTag::labelCenterCenter($midX, $midY, $labelW, 1.6, "\$aaa" . ($page + 1) . "/" . $pageCount);
            $pagerInner .= XmlTag::quadIcon64($nextX, 0, 1.6, 'ArrowNext', $nextAct);

            $xml .= XmlTag::frame($tableX, $pagerY, 0.2, $pagerInner);
        }

        return $xml;
    }
}
