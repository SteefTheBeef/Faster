<?php


class QualificationPanelBuilder {
    static function build(UmPanelRenderContext $ctx) {

        $selectedSubTab = $ctx->umState->getSelectedSubTab($ctx->login);
        $items = array(
            array('title' => 'Leaderboard', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD),
            array('title' => 'Rally', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY),
            array('title' => 'Speed', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED),
            array('title' => 'Alpine', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE),
            array('title' => 'Coast', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST),
            array('title' => 'Island', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND),
            array('title' => 'Bay', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY),
            array('title' => 'Stadium', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM),
        );

        $sub = SubTabs::bottom($ctx->login, $ctx->layout, UmPanelKeys::ACT_TAB_QUALIFICATION, $items, $selectedSubTab, array(
            'submenuR' => 0.0,
            'rowH' => 2.8,
            'bottomY' => -56.49,

            'autoWidth' => true,
            'fill' => true,   // use the unused space
            'tabLift' => 0.5,    // adjust 0.3..0.8 if needed

            'itemGap' => 0.0,
            'textsize' => 1.10,
            'tabPadLR' => 0.7,
            'tabMinW' => 3.4,
            'tabMaxW' => 40.0,
        ));

        switch ($selectedSubTab) {
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD:
                $contentXml = LeaderboardPanel::render($ctx);
                break;

            default:
                $contentXml = EnviLeaderboardPlayerPanel::render($ctx);
                break;
        }

        return $contentXml . $sub['xml'];
    }

    static function envi(Layout $layout, UMConfig $umConfig) {
        return RightPanel::buildTitle($layout, 'Qualification: Rally');
    }
}