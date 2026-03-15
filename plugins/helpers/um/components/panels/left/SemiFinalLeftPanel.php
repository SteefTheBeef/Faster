<?php


class SemiFinalLeftPanel {
    static function build(UmPanelRenderContext $ctx) {
        $xml = '';
        $title = '';
        $selectedSubTab = $ctx->umState->getSelectedSubTab($ctx->login);
        $prevSubTab = $ctx->umState->prevSubTab[$ctx->login];

        switch ($prevSubTab) {
            case UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS:
                $title = 'Semi-Final: Leaderboard';
                $xml .= PlayerListPlayoffsPanel::build($ctx, PlayerListPlayoffsPanel::DISPLAY_POINTS);
                break;
            case UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_STINTS:
                $title = 'Semi-Final: Stints';
                $xml .= RaceListLeftPanel::render($ctx, PlayerListPlayoffsPanel::DISPLAY_POINTS);
                break;
                default:

        }

        $title = RightPanel::buildTitle2($ctx->layout, $title);
        return $title . $xml;
    }
}