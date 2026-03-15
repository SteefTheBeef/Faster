<?php


class QualiPlayerListPanelBuilder {
    static function build(UmPanelRenderContext $ctx) {
        $title = '';
        $xml = PlayerListPlayoffsPanel::build($ctx, PlayerListPlayoffsPanel::DISPLAY_TIME);

        $prevSubTab = $ctx->umState->prevSubTab[$ctx->login];
        switch ($prevSubTab) {
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Rally');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Speed');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Alpine');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Coast');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Island');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Bay');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Stadium');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD:
                $title = RightPanel::buildTitle2($ctx->layout, 'Qualification: Leaderboard');
                $xml = PlayerListPlayoffsPanel::build($ctx);
                break;
            default:
                break;
        }

        return $title . $xml;

    }
}