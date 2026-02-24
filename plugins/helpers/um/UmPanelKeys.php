<?php

class UmPanelKeys {
    // Manialink idname
    const ML_ID_PANEL = 'umPanel';

    // Per-player ML state keys
    const ML_PANEL_CLOSED = 'um.panel.closed';
    const ML_TAB = 'um.tab';
    const ML_RACES_PAGE = 'player.races.page';

    // State key prefix for subtabs (value stored under "um.subtab.<tabKey>")
    const ML_SUBTAB_PREFIX = 'um.subtab.';

    // ---------------- Tab keys (UI state values) ----------------
    const TAB_PLAYERS = 'players';
    const TAB_STINTS = 'stints';
    const TAB_PRIZE = 'prize';
    const TAB_SCHEDULE = 'schedule';
    const TAB_RULES = 'rules';
    const TAB_INFORMATION = 'information';

    // Actions (static)
    const ACT_PANEL_CLOSE = 'um.panel.close';
    const ACT_PANEL_OPEN = 'um.panel.open';
    const ACT_RACES_PREV = 'races.prev';
    const ACT_RACES_NEXT = 'races.next';

    // Action prefixes (dynamic groups)
    const ACT_SCOREBOARD_ROW_PREFIX = 'umScoreBoardPlayerActions.';
    const ACT_TAB_PREFIX = 'um.tab.';
    const ACT_SUBTAB_PREFIX = 'um.subtab.';

    // Known tab actions used in init (and by UmPanelTabs)
    const ACT_TAB_PLAYERS = 'um.tab.players';
    const ACT_TAB_STINTS = 'um.tab.stints';
    const ACT_TAB_PRIZE = 'um.tab.prize';
    const ACT_TAB_SCHEDULE = 'um.tab.schedule';
    const ACT_TAB_RULES = 'um.tab.rules';
    const ACT_TAB_INFORMATION = 'um.tab.information';

    // Subtabs (rules)
    const ACT_SUBTAB_RULES_QUALIFICATION = 'um.subtab.rules.qualification';
    const ACT_SUBTAB_RULES_QUALIFICATION_POINTS = 'um.subtab.rules.qualification-points';
    const ACT_SUBTAB_RULES_SEMI_FINAL = 'um.subtab.rules.semi-final';
    const ACT_SUBTAB_RULES_MISC = 'um.subtab.rules.misc';

    /**
     * Build scoreboard row action name.
     *
     * @param int $rowIndex
     * @return string
     */
    public static function scoreboardRowAction($rowIndex) {
        return self::ACT_SCOREBOARD_ROW_PREFIX . (int)$rowIndex;
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

            self::ACT_TAB_PLAYERS,
            self::ACT_TAB_STINTS,
            self::ACT_TAB_PRIZE,
            self::ACT_TAB_SCHEDULE,
            self::ACT_TAB_RULES,
            self::ACT_TAB_INFORMATION,

            self::ACT_SUBTAB_RULES_QUALIFICATION,
            self::ACT_SUBTAB_RULES_QUALIFICATION_POINTS,
            self::ACT_SUBTAB_RULES_SEMI_FINAL,
            self::ACT_SUBTAB_RULES_MISC,
        );
    }
}