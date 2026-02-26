<?php


class QualiPlayerListPanelBuilder {
    static function build(UmPanelRenderContext $ctx) {
        $title = '';
        $xml = PlayerListPlayoffsPanel::build($ctx, PlayerListPlayoffsPanel::DISPLAY_TIME);
        switch ($ctx->activeSubtabAction) {
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY:
                $title = RightPanel::buildTitle2($ctx->layout, 'Rally');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED:
                $title = RightPanel::buildTitle2($ctx->layout, 'Speed');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE:
                $title = RightPanel::buildTitle2($ctx->layout, 'Alpine');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST:
                $title = RightPanel::buildTitle2($ctx->layout, 'Coast');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND:
                $title = RightPanel::buildTitle2($ctx->layout, 'Island');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY:
                $title = RightPanel::buildTitle2($ctx->layout, 'Bay');
                break;
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM:
                $title = RightPanel::buildTitle2($ctx->layout, 'Stadium');
                break;
            default:
                break;
        }

        return $title . $xml['xmlPlayers'];

    }
}