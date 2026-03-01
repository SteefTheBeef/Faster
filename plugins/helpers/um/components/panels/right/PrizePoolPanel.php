<?php

class PrizePoolPanel {
    static function render(UmPanelRenderContext $ctx) {
        $layout = $ctx->layout;
        $umState = $ctx->umState;

        // Keep table out of the submenu area.
        $reservedForSubmenu = 17.0 + 0.8; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 0.0;
        $tableTopGap = 3.2; // how far below the subheader the table starts (tweak to taste)

        $xml = UMPanel::textLabel($layout, '$fffGrand-Final Overall Rank Rewards', $subHeaderYOffset, true);
        //$amounts = $umState->donations;
        //$amountsByLogin = array_column($umState->donations, 'Amount', null);

        $cfg = $umState->prizePool->config;
        $gfRanks = array_column($cfg['GfRankDistribution'], 'Rank', null);
        $gfAmounts = array_column($cfg['GfRankDistribution'], 'Amount', null);

        $columns = array(
            array('header' => 'Rank', 'data' => $gfRanks, 'halign' => 'left'),
            array('header' => 'Amount(EUR)', 'data' => $gfAmounts, 'halign' => 'right')
        );

        $xml .= TableBuilder::build(
            $layout,
            count($gfRanks),
            $layout->geometry->panelWidth * (1 / 2),
            $tableTopGap,
            0.0,
            $columns
        );

        // OTHER REWARDS STARTS HERE
        $xml .= XmlTag::label(1, -22, 20, 3, "\$fff\$oOther Rewards");

        $columnsPerStint = array(
            array('header' => 'Reward Type', 'data' => array(
                'Grand-Final Stint Win',
                'Semi-Final Stint Win',
                'Qualification Best Race/Per Map',
                'Qualification Best Lap/Per Map',
                'Most popular map by vote'
            ),
                'halign' => 'left'),
            array('header' => 'Amount(EUR)', 'data' => array(
                $cfg['Stints']['GrandFinal'],
                $cfg['Stints']['SemiFinal'],
                $cfg['Qualification']['BestRacePerEnvironment'],
                $cfg['Qualification']['BestLapPerEnvironment'],
                $cfg['BestMap']
            ),
                'halign' => 'right'),
        );
        $xml .= TableBuilder::build(
            $layout,
            5,
            $layout->geometry->panelWidth * (1 / 2),
            21,
            0,
            $columnsPerStint
        );

        // DONATIONS STARTS HERE
        $xml .= XmlTag::label($layout->geometry->panelWidth * (1 / 2) + 1, -4, 20, 3, "\$fff\$oDonations");

        $donations = $umState->prizePool->donations;
        $columnsDonations = array(
            array('header' => 'Name', 'data' => array_column($donations, 'NickNameWithColor'), 'halign' => 'left'),
            array('header' => 'Amount(EUR)', 'data' => array_column($donations, 'Amount'), 'halign' => 'right')
        );

        $xml .= TableBuilder::build(
            $layout,
            count($donations),
            $layout->geometry->panelWidth * (1 / 2),
            $tableTopGap,
            $layout->geometry->panelWidth * (1 / 2),
            $columnsDonations
        );

        $xml .= XmlTag::label($layout->geometry->panelWidth * (1 / 2) + 1, -4, 20, 3, "\$fff\$oDonations");

        // TOTAL DONATIONS AND PRIZE POOL
        $accentColor = $layout->theme->accentTextColor;
        $xml .= XmlTag::label(1, -40, 20, 3, "\$fff\$oGuaranteed Prize Pool");

        $text = "\$fffA prize pool like this is only possible through the generosity of the listed individuals. "
            . "The donations and prize pool amounts to \$o" . $accentColor . $cfg['DonationsTotalAmount'];

        $xml .= XmlTag::label(1, -43, 20, 3, $text);
        $panelTitle = RightPanel::buildTitle($layout, 'Prize Pool');
        return $panelTitle . $xml;
    }
}