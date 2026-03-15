<?php


class SemiFinalPanel {
    static function build(UmPanelRenderContext $ctx) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;
        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 0.0;
        // how far below the subheader the table starts (tweak to taste)
        $tableTopGap = 3.2;

        $selectedSubTab = $ctx->umState->getSelectedSubTab($ctx->login);
        $items = array(
            array('title' => 'Player Details', 'action' => UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS),
            array('title' => 'Stints', 'action' => UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_STINTS),
        );

        $sub = SubTabs::bottom($ctx->login, $ctx->layout, UmPanelKeys::ACT_TAB_SEMI_FINAL, $items, $selectedSubTab, array(
            'submenuR' => 0.0,
            'rowH' => 2.8,
            'bottomY' => -56.49,

            'autoWidth' => false,
            'fill' => true,  // use the unused space
            'tabLift' => 0.5,    // adjust 0.3..0.8 if needed

            'itemGap' => 0.0,
            'textsize' => 1.10,
            'tabPadLR' => 0.7,
            'tabMinW' => 3.4,
            'tabMaxW' => 40.0,
        ));
        $contentXml = '';
        switch ($selectedSubTab) {
            case UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS:
                $contentXml .= SemiFinalPlayerDetails::render($ctx);
                break;
            case UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_STINTS:
                $contentXml .= SemiFinalRaces::render($ctx);
                break;
        }

        return $contentXml . $sub['xml'];
    }
}