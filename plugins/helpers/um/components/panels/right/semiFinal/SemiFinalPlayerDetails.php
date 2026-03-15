<?php


class SemiFinalPlayerDetails {
    static function render(UmPanelRenderContext $ctx) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;
        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 0.0;
        // how far below the subheader the table starts (tweak to taste)
        $tableTopGap = 3.2;

        //$selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        if (!isset($ctx->umState->selectedPlayer[$ctx->login])) {
            return '';
        }



        $selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        // Keep table out of the submenu area.

        //console(print_r($selectedPlayer, true));

        $xml = UMPanel::textLabel($layout, '$fffSemi-Final Score TEST', $subHeaderYOffset, true);

        $races = $selectedPlayer['Races'];
        $envis = array('');
        $counts = array('Qualification Score');
        $ranks = array('');
        $times = array('');
        $points = array($selectedPlayer['QualificationScore']);

        $total = $selectedPlayer['QualificationScore'];
        foreach ($races as $index => $race) {
            $counts[] = $index + 1;
            $rank = $race['Score']['Rank'];
            $font = $rank <= 3 ? "\$fc0" : "\$fff";

            $envis[] = $race['RaceInfo']['Environment'];
            $ranks[] = $font . $race['Score']['Rank'];
            $times[] = $font . $race['Score']['Time'];
            $points[] = $font . $race['AwardedPoints'];
            $total += $race['AwardedPoints'];
        }

        $counts[] = 'Total';
        $points[] = $total;

        $columns = array(
            array('header' => '#', 'data' => $counts, 'halign' => 'left', 'rank' => false),
            array('header' => 'Envi', 'data' => $envis, 'halign' => 'left'),
            array('header' => 'Rank', 'data' => $ranks, 'halign' => 'right'),
            array('header' => 'Time', 'data' => $times, 'halign' => 'right'),
            array('header' => 'Points', 'data' => $points, 'halign' => 'right'),
        );

        $xml .= RightPanel::buildTitle($layout, $selectedPlayer['NickNameWithColor']) . TableBuilder::build(
                $layout,
                count($counts),
                0,
                $tableTopGap,
                0.0,
                $columns
            );


        return $xml;
    }
}