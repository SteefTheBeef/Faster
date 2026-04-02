<?php

class UmBoard {
    public static function buildPanelXml(UmPanelRenderContext $ctx) {
        if (!isset($ctx->umState->boardIsOpen[$ctx->login])) {
            $ctx->umState->boardIsOpen[$ctx->login] = true;
        }

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

    private static function buildLeftPanelXml(UmPanelRenderContext $ctx) {
        //console("BUILD LEFT PANEL: " . print_r($ctx->umState->lastLeftPanel, true));
        switch ((string)$ctx->activeTabAction) {
            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return QualiPlayerListPanelBuilder::build($ctx);
            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
                return SemiFinalLeftPanel::build($ctx);
            case UmPanelKeys::ACT_TAB_GRAND_FINAL:
                return GrandFinalLeftPanel::build($ctx);
            default:
                if ($ctx->umState->prevTab[$ctx->login] === UmPanelKeys::ACT_TAB_SEMI_FINAL) {
                    return SemiFinalLeftPanel::build($ctx);
                }
                if ($ctx->umState->prevTab[$ctx->login] === UmPanelKeys::ACT_TAB_GRAND_FINAL) {
                    return GrandFinalLeftPanel::build($ctx);
                }
                return QualiPlayerListPanelBuilder::build($ctx);
        }
    }

    private static function buildRightPanelBodyXml(UmPanelRenderContext $ctx) {
        switch ((string)$ctx->activeTabAction) {
            case UmPanelKeys::ACT_TAB_SCHEDULE:
                return SchedulePanelBuilder::schedule($ctx->layout, $ctx->umConfig);

            case UmPanelKeys::ACT_TAB_RULES:
                return RulesPanelBuilder::build($ctx);

            case UmPanelKeys::ACT_TAB_QUALIFICATION:
                return QualificationPanelBuilder::build($ctx);

            case UmPanelKeys::ACT_TAB_MAPS:
                return MapsPanel::build($ctx);

            case UmPanelKeys::ACT_TAB_SEMI_FINAL:
                return SemiFinalPanel::build($ctx);
            case UmPanelKeys::ACT_TAB_GRAND_FINAL:
                return GrandFinalPanel::build($ctx);
            default:
                //return 'PlayerRacesPanel::build($ctx->login, $ctx->selectedPlayerForLogin, $ctx->layout, $ctx->mlAct)';
                return '';
        }
    }

    public static function handleAction($login, $action) {
        global $umState, $umConfig;
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
            if (!isset($selectedPlayer[$login]) || !isset($selectedPlayer[$login]['Races'])) {
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
            $umState->setSelectedPlayerIndex($login, $action);
            return true;
        }

        if (strpos($action, UmPanelKeys::ACT_RATE_MAP_DOWN) === 0) {
            self::saveMapRatings($login, $action);
        }

        if (strpos($action, UmPanelKeys::ACT_RATE_MAP_UP) === 0) {
            //console("MAP UP" . print_r($action, true));
            self::saveMapRatings($login, $action, UmPanelKeys::ACT_RATE_MAP_UP);
        }

        return false;
    }

    static function saveMapRatings($login, $action, $actRateMapDownOrUp = UmPanelKeys::ACT_RATE_MAP_DOWN) {
        global $umState, $umConfig;
        $index = (int)substr($action, strlen($actRateMapDownOrUp) + 1);

        $ratingsForLogin = MapRatingsService::getRatingForLogin($login, $umState->mapRatingsTA, $umConfig->um4QualiBestRace->maps);
        $i = 0;
        foreach ($ratingsForLogin as $mapId => &$mapRating) {
            $mapRating['Rank'] = $i;

            if ($actRateMapDownOrUp === UmPanelKeys::ACT_RATE_MAP_DOWN) {
                // this is the actual map that we want to downvote
                if ($i === $index) {
                    $mapRating['Rank'] = $i + 1;
                }
                // the next map needs to move up one place
                if ($i === $index + 1) {
                    $mapRating['Rank'] = $i + -1;
                }
            } else {
                // this is the map that is above, and should move down one place.
                if ($i === $index - 1) {
                    $mapRating['Rank'] = $i + 1;
                }
                // this is the actual map that we want to upvote
                if ($i === $index) {
                    $mapRating['Rank'] = $i + -1;
                }
            }
            $i++;
        }

        MapRatingsService::sortRatingsForLoginByRank($ratingsForLogin);
        $umState->mapRatingsTA[$login] = $ratingsForLogin;
        //console(print_r($ratingsForLogin, true));
    }
}
