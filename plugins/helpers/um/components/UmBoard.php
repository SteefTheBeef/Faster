<?php

class UmBoard {
    public static function buildPanelXml(UmPanelRenderContext $ctx) {
        if ($ctx->umState->boardIsOpen[$ctx->login] === false) {
            return OpenCloseToggle::render($ctx);
        }

        $markup = $ctx->layout->markup;
        $ctx->activeTabAction = $ctx->umState->getSelectedTab($ctx->login);
        $ctx->activeSubtabAction = $ctx->umState->getSelectedSubTab($ctx->login);

        $leftPanelXml =
            $markup->leftFrameStart
            . BackgroundPanel::left($ctx->layout)
            . self::buildLeftPanelXml($ctx)
            . $markup->leftFrameEnd;

        $rightPanelXml =
            $markup->rightFrameStart
            . Tabs::render($ctx->layout, $ctx->activeTabAction, $ctx->mlAct)
            . OpenCloseToggle::render($ctx)
            . BackgroundPanel::right($ctx->layout)
            . self::buildRightPanelBodyXml($ctx)
            . $markup->rightFrameEnd;

        return XmlTag::frame(0, 0, 5,
            BoardTitle::render($ctx->layout, 'UNITED MASTERS 4')
            . BoardBorders::render($ctx->layout)
            . $leftPanelXml
            . $rightPanelXml
        );
    }

    public static function handleAction($login, $action) {
        global $umState;
        $umState = (object)$umState;

        // Panel close/open
        if ($action === UmPanelKeys::ACT_PANEL_CLOSE) {
            $umState->boardIsOpen[$login] = false;
            return true;
        }

        if ($action === UmPanelKeys::ACT_PANEL_OPEN) {
            $umState->boardIsOpen[$login] = true;
            return true;
        }

        // Subtabs: store the *full action* under "<tabAction>.subtab"
        // Example stored value: 'um.tab.rules.subtab.qualification'
        if (strpos($action, UmPanelKeys::ACT_TAB_PREFIX) === 0 && strpos($action, UmPanelKeys::ACT_SUBTAB_IDENTIFIER . '.') !== false) {
            $umState->setSelectedSubTab($login, $action);
            return true;
        }

        // Tabs: store the tab *action* as state (single identifier)
        // Tabs needs to come after subtabs.
        if (strpos($action, UmPanelKeys::ACT_TAB_PREFIX) === 0) {
            $umState->setSelectedTab($login, (string)$action);
            return true;
        }

        if ($action === UmPanelKeys::ACT_PLAYERS_PREV || $action === UmPanelKeys::ACT_PLAYERS_NEXT) {
            $umState->setSelectedPlayerPaginationIndex($login, $action);
        }

        // Paging races for a player
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
        }        // Paging races for a player

        // Select player

        $rowPrefix = UmPanelKeys::ACT_PLAYERS_SELECT;
        if (strpos($action, $rowPrefix) === 0) {
            console('Selected player row index: ' . $action);
            $rowIndex = (int)substr($action, strlen($rowPrefix));
            console('index: ' . $rowIndex);
            $umState->selectedPlayerIndex[$login] = $rowIndex;
            return true;
        }

        return false;
    }

    private static function buildLeftPanelXml(UmPanelRenderContext $ctx) {
        switch ($ctx->activeTabAction) {
            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
                $listBuild = PlayerListPlayoffsPanel::build(
                    $ctx->login,
                    $ctx->layout,
                    $ctx->players,
                    $ctx->selectedRow,
                    $ctx->actionIds,
                    $ctx->mlAct
                );
                return isset($listBuild['xmlPlayers']) ? $listBuild['xmlPlayers'] : '';
            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                console("ACT_TAB_QUALIFICATION");
                return QualiPlayerListPanelBuilder::build($ctx);
            default:
                return '';

        }
    }

    private static function buildLeftPanelBodyXml(UmPanelRenderContext $ctx) {
        switch ((string)$ctx->activeSubtabAction) {
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY:


            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED:

            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE:
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST:
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND:
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY:
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM:
                return UMPanel::textLabel($ctx->layout, 'Rules navigation goes here...');

            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return UMPanel::textLabel($ctx->layout, 'Qualification filters / groups go here...');

            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return UMPanel::textLabel($ctx->layout, 'Schedule overview / round list goes here...');

            case UmPanelKeys::ACT_TAB_INFORMATION:
                return UMPanel::textLabel($ctx->layout, 'Info shortcuts / links go here...');

            case UmPanelKeys::ACT_TAB_PRIZE:
                return UMPanel::textLabel($ctx->layout, 'Prize categories go here...');

            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
            default:
                return UMPanel::textLabel($ctx->layout, 'Select a tab...');
        }
    }

    private static function buildRightPanelBodyXml(UmPanelRenderContext $ctx) {
        switch ((string)$ctx->activeTabAction) {
            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return SchedulePanelBuilder::schedule($ctx->layout, $ctx->umConfig);

            case UmPanelKeys::ACT_TAB_RULES:
                return RulesPanelBuilder::build($ctx->login, $ctx->layout, $ctx->umConfig, $ctx->umState);

            case UmPanelKeys::ACT_TAB_INFORMATION:
                return InformationPanelBuilder::getInformationPanel($ctx->layout);

            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return QualificationPanelBuilder::build($ctx->login, $ctx->layout, $ctx->umConfig, $ctx->umState);

            case UmPanelKeys::ACT_TAB_PRIZE:
                return '';

            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
            default:
                return PlayerRacesPanel::build($ctx->login, $ctx->selectedPlayerForLogin, $ctx->layout, $ctx->mlAct);
        }
    }

}
