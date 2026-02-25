<?php

require_once __DIR__ . '/layout/Layout.php';
require_once __DIR__ . '/../utils/XmlTag.php';
require_once __DIR__ . '/UmPanelTabs.php';
require_once __DIR__ . '/UmPanelKeys.php';
require_once __DIR__ . '/panels/QualificationPanelBuilder.php';
require_once __DIR__ . '/panels/left/PlayerListPlayoffsPanel.php';
require_once __DIR__ . '/panels/PlayerRacesPanel.php';
require_once __DIR__ . '/../utils/MLState.php';

class UmPanelRenderer {
    public static function buildPanelXml($login, Layout $layout, array $players, $selectedRow, $selectedPlayerForLogin, array $actionIds, array $mlAct, UMConfig $umConfig, UMState $umState) {
        $isClosed = ((int)MLState::mlGet($login, UmPanelKeys::ML_PANEL_CLOSED, 0) === 1);
        if ($isClosed) {
            return self::buildClosedToggleXml($layout, $mlAct);
        }

        $panelHeaderTitle = 'UNITED MASTERS 4';
        $panelHeaderXml = self::buildPanelHeaderTitleXml($layout, $panelHeaderTitle);
        $bordersXml = self::buildPanelBordersXml($layout);

        // Decide active tab/subtab ONCE and route both sides from it
        $activeTabAction = self::getActiveTabAction($login);
        $activeSubtabAction = self::getActiveSubtabAction($login, $activeTabAction);

        $leftPanelXml = self::buildLeftPanelXml(
            $login,
            $activeTabAction,
            $activeSubtabAction,
            $layout,
            $players,
            (int)$selectedRow,
            $actionIds,
            $selectedPlayerForLogin,
            $mlAct,
            $umConfig,
            $umState
        );

        $rightBuild = self::buildRightPanelXml($login, $layout, $selectedPlayerForLogin, $mlAct, $umConfig, $umState);
        $rightPanelXml =
            $rightBuild['panelFrameStart']
            . $rightBuild['tabsXml']
            . $rightBuild['closeXml']
            . $rightBuild['panelBgQuad']
            . $rightBuild['panelTitle']
            . $rightBuild['panelBody']
            . $rightBuild['panelFrameEnd'];

        return XmlTag::frame(0, 0, 5,
            $panelHeaderXml
            . $bordersXml
            . $leftPanelXml
            . $rightPanelXml
        );
    }
    private static function buildPanelHeaderTitleXml(Layout $layout, $text) {
        $leftX = $layout->geometry->borderLeftX;
        $outerW = $layout->geometry->borderOuterWidth;
        $topY = $layout->geometry->borderTopY;

        $titleH = 3.0;
        $titleW = $outerW;
        $centerX = $leftX + ($outerW / 2.0);

        // Move this up/down to match your red line precisely:
        $y = $topY + 6.6;

        // If your layout theme has a font style you like, you can use it here.
        $font = $layout->theme->panelTitleFontStyle;

        return XmlTag::labelCenterCenter(
            $centerX,
            $y,
            $titleW,
            $titleH,
            $font . $text,
            null,
            array('textsize' => 3.2, 'z' => 0.8)
        );
    }
    public static function handleAction($login, $action, $answer, array &$selectedRowByLogin, array &$selectedPlayer = null) {
        global $_players, $umScoreBoardPlayers;

        // Panel close/open
        if ($action === UmPanelKeys::ACT_PANEL_CLOSE) {
            MLState::mlSet($login, UmPanelKeys::ML_PANEL_CLOSED, 1);
            return true;
        }

        if ($action === UmPanelKeys::ACT_PANEL_OPEN) {
            MLState::mlSet($login, UmPanelKeys::ML_PANEL_CLOSED, 0);
            return true;
        }


        // Subtabs: store the *full action* under "um.subtab.<tabKey>"
        // Example stored value: 'um.subtab.rules.qualification'

        // Subtabs: store the *full action* under "<tabAction>.subtab"
        // Example stored value: 'um.tab.rules.subtab.qualification'
        if (strpos($action, UmPanelKeys::ACT_TAB_PREFIX) === 0 && strpos($action, UmPanelKeys::ACT_SUBTAB_IDENTIFIER . '.') !== false) {
            $parts = explode(UmPanelKeys::ACT_SUBTAB_IDENTIFIER, $action, 2);
            $tabAction = isset($parts[0]) ? (string)$parts[0] : '';
            $subRest = isset($parts[1]) ? (string)$parts[1] : '';

            // $subRest should begin with ".something"
            if ($tabAction !== '' && $subRest !== '' && $subRest[0] === '.') {
                //console($action);
                //console($tabAction);
                MLState::mlSet($login, $tabAction . UmPanelKeys::ACT_SUBTAB_IDENTIFIER, (string)$action);
                return true;
            }

            return false;
        }

        // Tabs: store the tab *action* as state (single identifier)
        // Tabs needs to come after subtabs.
        if (strpos($action, UmPanelKeys::ACT_TAB_PREFIX) === 0) {
            MLState::mlSet($login, UmPanelKeys::ML_TAB, (string)$action);
            return true;
        }

        // Paging
        if ($action === UmPanelKeys::ACT_RACES_PREV || $action === UmPanelKeys::ACT_RACES_NEXT) {
            if (!isset($selectedPlayer[$login])) {
                return true; // known action, nothing to do
            }

            $pageCount = UMPanel::racesPageCount($selectedPlayer[$login]['Races']);
            $page = (int)MLState::mlGet($login, UmPanelKeys::ML_RACES_PAGE, 0);

            if ($action === UmPanelKeys::ACT_RACES_PREV) $page--;
            if ($action === UmPanelKeys::ACT_RACES_NEXT) $page++;

            $page = UMPanel::clampInt($page, 0, $pageCount - 1);
            MLState::mlSet($login, UmPanelKeys::ML_RACES_PAGE, $page);
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

    private static function buildClosedToggleXml(Layout $layout, array $mlAct) {
        $x = $layout->geometry->closeButtonX;
        $y = $layout->geometry->closeButtonY;
        $s = $layout->geometry->closeButtonSize;

        $actId = isset($mlAct[UmPanelKeys::ACT_PANEL_OPEN]) ? (int)$mlAct[UmPanelKeys::ACT_PANEL_OPEN] : 0;

        return XmlTag::frame($x, $y, 5, XmlTag::quadIcon64(0, 0, $s, 'Check', $actId));
    }

    private static function getActiveTabAction($login) {
        $activeTabRaw = (string)MLState::mlGet($login, UmPanelKeys::ML_TAB, 'Qualification');
        $tabs = UmPanelTabs::getTabs();
        return UmPanelTabs::getActiveTabAction($activeTabRaw, $tabs);
    }

    /**
     * Returns the stored subtab action for a given tab action.
     * Stored by handleAction() under "<tabAction>.subtab"
     *
     * Example returned value: "um.tab.rules.subtab.qualification"
     */
    private static function getActiveSubtabAction($login, $activeTabAction) {
        $key = (string)$activeTabAction . UmPanelKeys::ACT_SUBTAB_IDENTIFIER; // ".subtab"
        $raw = (string)MLState::mlGet($login, $key, '');

        // If nothing stored yet, just return empty string; callers can decide a default.
        if ($raw === '') {
            return '';
        }

        // Very light validation: ensure it still belongs to the active tab.
        if (strpos($raw, (string)$activeTabAction . UmPanelKeys::ACT_SUBTAB_IDENTIFIER . '.') !== 0) {
            return '';
        }

        return $raw;
    }


    private static function buildRightPanelXml($login, Layout $layout, $selectedPlayerForLogin, array $mlAct, UMConfig $umConfig, UMState $umState) {
        $panelW = $layout->geometry->panelWidth;
        $panelH = $layout->geometry->panelHeight;

        $tabs = UmPanelTabs::getTabs();
        $activeTabAction = self::getActiveTabAction($login);

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
        $titleText = ($activeTabAction === UmPanelKeys::ACT_TAB_SEMI_FINAL)
            ? $selectedPlayerName
            : UmPanelTabs::getTitleByAction($activeTabAction, $tabs);

        $font = ($activeTabAction === UmPanelKeys::ACT_TAB_SEMI_FINAL) ? '' : $layout->theme->headerFontStyle;

        $panelTitle = XmlTag::label(1, -1, $panelW - 2, 3, $font . $titleText, null, array('textsize' => 2));

        $panelBody = self::buildRightPanelBodyXml($login, $activeTabAction, $selectedPlayerForLogin, $layout, $umConfig, $umState, $mlAct);

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

    /**
     * Builds the whole left side (frame start + body + frame end).
     * This is where you decide: player list vs tab/subtab-specific left content.
     */
    private static function buildLeftPanelXml(
        $login,
        $activeTabAction,
        $activeSubtabAction,
        Layout $layout,
        array $players,
        $selectedRow,
        array $actionIds,
        $selectedPlayerForLogin,
        array $mlAct,
        UMConfig $umConfig,
        UMState $umState
    ) {
        $bodyXml = '';

        // Default behavior: keep player list on "player-centric" tabs.
        // Expand/adjust this switch as your UX evolves.
        switch ((string)$activeTabAction) {
            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
                $listBuild = PlayerListPlayoffsPanel::build($layout, $players, (int)$selectedRow, $actionIds);
                $bodyXml = isset($listBuild['xmlPlayers']) ? $listBuild['xmlPlayers'] : '';
                break;

            default:
                // Tab/subtab-specific left content (NOT the right panel body)
                $bodyXml = self::buildLeftPanelBodyXml(
                    $login,
                    $activeTabAction,
                    $activeSubtabAction,
                    $selectedPlayerForLogin,
                    $layout,
                    $umConfig,
                    $umState,
                    $mlAct
                );
                break;
        }

        return $layout->markup->playerFrameStart
            . $bodyXml
            . $layout->markup->playerFrameEnd;
    }

    /**
     * Left panel routing (tab + subtab aware).
     * IMPORTANT: Keep this focused on *left-side* needs (navigation, lists, contextual info),
     * not the main content already handled by buildRightPanelBodyXml().
     */
    private static function buildLeftPanelBodyXml($login, $activeTabAction, $activeSubtabAction, $selectedPlayer, Layout $layout, UMConfig $umConfig, UMState $umState, array $mlAct) {
        switch ((string)$activeTabAction) {
            case UmPanelKeys::ACT_TAB_RULES:
                // Example: you can switch further based on $activeSubtabAction
                // if ($activeSubtabAction === 'um.tab.rules.subtab.something') { ... }
                return UMPanel::textLabel($layout, 'Rules navigation goes here...');

            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return UMPanel::textLabel($layout, 'Qualification filters / groups go here...');

            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return UMPanel::textLabel($layout, 'Schedule overview / round list goes here...');

            case UmPanelKeys::ACT_TAB_INFORMATION:
                return UMPanel::textLabel($layout, 'Info shortcuts / links go here...');

            case UmPanelKeys::ACT_TAB_PRIZE:
                return UMPanel::textLabel($layout, 'Prize categories go here...');

            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
            default:
                // Fallback: if you end up here, show something sane.
                return UMPanel::textLabel($layout, 'Select a tab...');
        }
    }
    private static function buildRightPanelBodyXml($login, $activeTabAction, $selectedPlayer, Layout $layout, UMConfig $umConfig, UMState $umState, array $mlAct) {
        switch ((string)$activeTabAction) {
            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return SchedulePanelBuilder::schedule($layout, $umConfig);
            case UmPanelKeys::ACT_TAB_RULES:
                return RulesPanelBuilder::build($login, $layout, $umConfig);
            case UmPanelKeys::ACT_TAB_INFORMATION:
                return InformationPanelBuilder::getInformationPanel($layout);
            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return QualificationPanelBuilder::build($login, $layout, $umConfig, $umState);
            case UmPanelKeys::ACT_TAB_PRIZE:
                return '';
            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
            default:
                return PlayerRacesPanel::build($login, $selectedPlayer, $layout, $mlAct);
        }
    }

}
