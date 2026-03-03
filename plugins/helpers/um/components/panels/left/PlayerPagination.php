<?php
class PlayerPagination {
    public static function render(UmPanelRenderContext $ctx, $players) {
        $geometry = $ctx->layout->geometry;
        $theme = $ctx->layout->theme;

        $page = $ctx->umState->getSelectedPlayerPaginationIndex($ctx->login);
        $pageCount = UMPanel::playersPageCount($players);

        $showPager = count($players) > PLAYERS_PER_PAGE;
        $canPrev = false;
        $canNext = false;

        if ($showPager) {
            $canPrev = ($page > 0);
            $canNext = ($page < $pageCount - 1);
        }

        $prevAct = $canPrev ? (int)$ctx->mlAct[UmPanelKeys::ACT_PLAYERS_PREV] : null;
        $nextAct = $canNext ? (int)$ctx->mlAct[UmPanelKeys::ACT_PLAYERS_NEXT] : null;

        return $showPager ?
            XmlTag::quad(-0.1, -$geometry->panelHeight - 0.1, $geometry->backgroundWidth + 0.2, 3, $theme->tabActiveBackgroundColor) .
            XmlTag::pagerPrevNext64(0, -$geometry->panelHeight - 1, 0.2, $geometry->playerWidth, $page, $pageCount, $prevAct, $nextAct, array('align' => 'right')) : '';
    }
}