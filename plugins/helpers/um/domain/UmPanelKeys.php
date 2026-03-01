<?php

class UmPanelKeys {
    // Manialink idname
    const ML_ID_PANEL = 'umBoard';
    const ML_ID_BOARD_MINI = 'umBoardMini';

    // Per-player ML state keys
    const ML_PANEL_CLOSED = 'um.panel.closed';
    const ML_TAB = 'um.tab';
    const ML_RACES_PAGE = 'player.races.page';

    // State key prefix for subtabs (value stored under "um.subtab.<tabKey>")
    const ML_SUBTAB_PREFIX = 'um.subtab.';

    // Actions (static)
    const ACT_PANEL_CLOSE = 'um.panel.close';
    const ACT_PANEL_OPEN = 'um.panel.open';

    const ACT_RACES_PREV = 'races.prev';
    const ACT_RACES_NEXT = 'races.next';

    const ACT_PLAYERS_PREV = 'players.prev';
    const ACT_PLAYERS_NEXT = 'players.next';
    const ACT_PLAYERS_SELECT = 'players.select';
    const ML_PLAYER_PAGE = 'players.page';

    // Action prefixes (dynamic groups)
    const ACT_TAB_PREFIX = 'um.tab.';
    const ACT_SUBTAB_IDENTIFIER = '.subtab';

    // Known tab actions used in init (and by UmPanelTabs)
    const ACT_TAB_QUALIFICATION = 'um.tab.qualification';
    const ACT_TAB_SEMI_FINAL = 'um.tab.semi-final';
    const ACT_TAB_STINTS = 'um.tab.stints';
    const ACT_TAB_PRIZE_POOL = 'um.tab.prize-pool';
    const ACT_TAB_SCHEDULE = 'um.tab.schedule';
    const ACT_TAB_RULES = 'um.tab.rules';
    const ACT_TAB_INFORMATION = 'um.tab.information';

    // Subtabs (rules)
    const ACT_SUBTAB_RULES_QUALIFICATION = self::ACT_TAB_RULES . self::ACT_SUBTAB_IDENTIFIER . '.qualification';
    const ACT_SUBTAB_RULES_QUALIFICATION_POINTS = self::ACT_TAB_RULES . self::ACT_SUBTAB_IDENTIFIER . '.qualification-points';
    const ACT_SUBTAB_RULES_SEMI_FINAL = self::ACT_TAB_RULES . self::ACT_SUBTAB_IDENTIFIER . '.semi-final';
    const ACT_SUBTAB_RULES_MISC = self::ACT_TAB_RULES . self::ACT_SUBTAB_IDENTIFIER . '.misc';

    // Subtabs (qualification)
    const ACT_SUBTAB_QUALIFICATION_LEADERBOARD = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.leaderboard';
    const ACT_SUBTAB_QUALIFICATION_RALLY = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.rally';
    const ACT_SUBTAB_QUALIFICATION_SPEED = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.speed';
    const ACT_SUBTAB_QUALIFICATION_ALPINE = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.alpine';
    const ACT_SUBTAB_QUALIFICATION_COAST = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.coast';
    const ACT_SUBTAB_QUALIFICATION_ISLAND = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.island';
    const ACT_SUBTAB_QUALIFICATION_BAY = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.bay';
    const ACT_SUBTAB_QUALIFICATION_STADIUM = self::ACT_TAB_QUALIFICATION . self::ACT_SUBTAB_IDENTIFIER . '.stadium';

    // Mini Board
    // Actions (static)
    const ACT_BOARD_MINI_TOGGLE = 'um.board.mini.toggle';


    /**
     * Build scoreboard row action name.
     *
     * @param int $rowIndex
     * @return string
     */
    public static function createPlayerSelectActionString($rowIndex) {
        return self::ACT_PLAYERS_SELECT . (int)$rowIndex;
    }

    /**
     * Build ML state key for the active subtab of a given tab.
     *
     * @param string $tabKey Example: 'rules'
     * @return string Example: 'um.subtab.rules'
     */
    public static function mlSubtabKey($tabKey) {
        return self::ML_SUBTAB_PREFIX . (string)$tabKey;
    }

    /**
     * @return array<int, string> list of action names to register in init
     */
    public static function actionsToRegister() {
        return array(
            self::ACT_PANEL_CLOSE,
            self::ACT_PANEL_OPEN,
            self::ACT_RACES_PREV,
            self::ACT_RACES_NEXT,

            self::ACT_PLAYERS_PREV,
            self::ACT_PLAYERS_NEXT,

            self::ACT_TAB_QUALIFICATION,
            self::ACT_TAB_SEMI_FINAL,
            self::ACT_TAB_STINTS,
            self::ACT_TAB_PRIZE_POOL,
            self::ACT_TAB_SCHEDULE,
            self::ACT_TAB_RULES,
            self::ACT_TAB_INFORMATION,

            self::ACT_SUBTAB_RULES_QUALIFICATION,
            self::ACT_SUBTAB_RULES_QUALIFICATION_POINTS,
            self::ACT_SUBTAB_RULES_SEMI_FINAL,
            self::ACT_SUBTAB_RULES_MISC,

            self::ACT_SUBTAB_QUALIFICATION_LEADERBOARD,
            self::ACT_SUBTAB_QUALIFICATION_RALLY,
            self::ACT_SUBTAB_QUALIFICATION_SPEED,
            self::ACT_SUBTAB_QUALIFICATION_ALPINE,
            self::ACT_SUBTAB_QUALIFICATION_COAST,
            self::ACT_SUBTAB_QUALIFICATION_ISLAND,
            self::ACT_SUBTAB_QUALIFICATION_BAY,
            self::ACT_SUBTAB_QUALIFICATION_STADIUM
        );
    }

    public static function getQualificationEnvironmentKeyBySubtabAction($action) {
        $map = array(
            self::ACT_SUBTAB_QUALIFICATION_RALLY   => 'Rally',
            self::ACT_SUBTAB_QUALIFICATION_SPEED   => 'Speed',
            self::ACT_SUBTAB_QUALIFICATION_ALPINE  => 'Alpine',
            self::ACT_SUBTAB_QUALIFICATION_BAY     => 'Bay',
            self::ACT_SUBTAB_QUALIFICATION_COAST   => 'Coast',
            self::ACT_SUBTAB_QUALIFICATION_ISLAND  => 'Island',
            self::ACT_SUBTAB_QUALIFICATION_STADIUM => 'Stadium',
        );

        return isset($map[$action]) ? $map[$action] : null;
    }

}