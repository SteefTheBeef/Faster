<?php


class GrandFinalLeftPanel {
    static function build(UmPanelRenderContext $ctx) {
        $xml = '';
        $title = '';
        $selectedSubTab = $ctx->umState->getSelectedSubTab($ctx->login);
        $prevSubTab = $ctx->umState->prevSubTab[$ctx->login];

        switch ($prevSubTab) {
            case UmPanelKeys::ACT_SUBTAB_GRAND_FINAL_PLAYER_DETAILS:
                $title = 'Grand-Final: Leaderboard';
                $xml .= PlayerListPlayoffsPanel::build($ctx,PlayerListPlayoffsPanel::DISPLAY_POINTS);
                break;
            case UmPanelKeys::ACT_SUBTAB_GRAND_FINAL_STINTS:
                $title = 'Grand-Final: Stints';
                $xml .= RaceListLeftPanel::render($ctx, $ctx->umState->grandFinalRaces, PlayerListPlayoffsPanel::DISPLAY_POINTS);
                break;
                default:

        }

        $title = RightPanel::buildTitle2($ctx->layout, $title);
        return $title . $xml;
    }
}