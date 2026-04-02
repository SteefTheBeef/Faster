<?php


class GrandFinalRaces {
    static function render(UmPanelRenderContext $ctx) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;
        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 0.0;
        // how far below the subheader the table starts (tweak to taste)
        $tableTopGap = 3.2;

        $selectedPlayer = $ctx->umState->selectedPlayer[$ctx->login];
        // Keep table out of the submenu area.

        $xml = UMPanel::textLabel($layout, '$fffGrand-Final Score', $subHeaderYOffset, true);

        //$races = $umState->semiFinalRaces;

        //console(print_r($umState->semiFinalRaces, true));

        if (!isset($umState->selectedGrandFinalRace[$ctx->login])) {
            BottomBar::render($ctx);
        }

        $selectedRace = $umState->selectedGrandFinalRace[$ctx->login];
        $rankings = $selectedRace['Scores'];
        $stintPrizeAmount = $umState->prizePool->config['Stints']['GrandFinal'];

        $playerNames = array();
        $ranks = array();
        $times = array();
        $laps = array();
        $cps = array();
        $points = array();
        $bestRaceTimes = array();
        $prizes = array();

        $pd = $ctx->umConfig->um4GF->pointsDistribution;
        $i = 0;
        foreach ($rankings as $index => $ranking) {

            if  ($i == 0) {
                $ranks[] = $ranking['Rank'] . ' (' . $stintPrizeAmount . ' EUR)';
            } else {
                $ranks[] = $ranking['Rank'];
            }
            $counts[] = $index + 1;

            //$ranks[] = $ranking['Rank'];
            $times[] = $ranking['Time'];
            $laps[] = $ranking['Lap'];
            $cps[] = $ranking['Checkpoints'];
            $points[] = $pd[$i];
            if (isset($selectedRace['Players'][$ranking['Login']])) {
                $playerNameArray = $selectedRace['Players'][$ranking['Login']];
                $playerNames[] = $playerNameArray['NickNameWithColor'];
            }
            $i++;
        }

        $columns = array(
            array('header' => 'Rank', 'data' => $ranks, 'halign' => 'left'),
            array('header' => 'Player', 'data' => $playerNames, 'halign' => 'left'),
            array('header' => 'Time', 'data' => $times, 'halign' => 'right'),
            array('header' => 'Points', 'data' => $points, 'halign' => 'right'),
            array('header' => 'Laps', 'data' => $laps, 'halign' => 'right'),
            array('header' => 'CPs', 'data' => $cps, 'halign' => 'right'),
        );
        $envi = $layout->theme->accentTextColor . $selectedRace['RaceInfo']['Environment'];
        $name = '$fff' . StringUtils::mlStripBold($selectedRace['RaceInfo']['ChallengeNameWithColor']);
        $date = '$fff' . $selectedRace['RaceInfo']['Date'];
        $divider = $layout->theme->accentTextColor . ' | ';
        $title = $envi . ' ' . $date . $divider . $name;
        $xml .= RightPanel::buildTitle($layout, $title) . TableBuilder::build(
                $layout,
                count($playerNames),
                0,
                $tableTopGap,
                0.0,
                $columns,
                1.55
            );

        return $xml;
    }
}