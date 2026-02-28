<?php

class RulesPanelBuilder {
    static function build($login, Layout $layout, UMConfig $umConfig, UmState $umState) {
        $selectedSubTab = $umState->getSelectedSubTab($login);
        $submenuItems = array(
            array('title' => 'Qualification', 'action' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION),
            array('title' => 'Qualification Points', 'action' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION_POINTS),
            array('title' => 'Semi-Final', 'action' => UmPanelKeys::ACT_SUBTAB_RULES_SEMI_FINAL),
            array('title' => 'Grand-Final', 'action' => UmPanelKeys::ACT_SUBTAB_RULES_MISC),
        );

        $sub = SubTabs::right(
            $login,
            $layout,
            UmPanelKeys::ACT_TAB_RULES,
            $submenuItems,
            $selectedSubTab,
            array(
                'submenuR' => 0.0,
                'submenuW' => 17.0,
                'gap' => 0.0,

                // NEW: let the builder validate+default
                'defaultAction' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION,
            )
        );

        switch ($selectedSubTab) {
            case UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION:
                $contentXml = self::qualification($layout, $umConfig);
                break;

            case UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION_POINTS:
                $contentXml = self::qualificationPoints($layout, $umConfig);
                break;

            case UmPanelKeys::ACT_SUBTAB_RULES_SEMI_FINAL:
                $contentXml = self::semiFinal($layout, $umConfig);
                break;

            default:
                $contentXml = self::grandFinal($layout, $umConfig);
                break;
        }

        return $contentXml . $sub['xml'];
    }

    static function qualification(Layout $layout, $umConfig) {
        $xml = UMPanel::textLabel($layout, '$fffQualification', 0, true);
        $accentColor = $layout->theme->accentTextColor;
        $panelTitle = RightPanel::buildTitle($layout, 'Rules: Qualification');
        $p1 = "This server will run seven carefully selected qualification maps in a continuous loop, "
            . "one after the other, for the whole duration of the qualification. "
            . "As this is a TMUF cup, one map for each of the seven environments has been picked."
            . "While the qualification is running you are free to drive as much as you want, no restrictions.";
        $xml .= UMPanel::textLabel($layout, $p1, 3.5);

        $p2 = "Each qualification race consists of one warmup lap and {$accentColor}four\$fff regular laps. "
            . "\$fffWhen a race is completed, the new times are recorded and added to our database. "
            . "If you happen improve your time for that specific map, "
            . "you will in most cases climb higher on the leaderboard.";
        $xml .= UMPanel::textLabel($layout, $p2, 14.5);

        $p3 = "The {$accentColor}top 24 players advances \$ffffrom the qualifications to the semi-final. "
            . "Points accumulated in the qualification will {$accentColor}carry-over to the semi-final.";
        $xml .= UMPanel::textLabel($layout, $p3, 24);

        $p4 = "\$oImportant: \$oOnly races driven on {$accentColor}this server "
            . "\$fffwill count towards your cumulative qualification results. "
            . "Records achieved on other servers are not valid.";
        $xml .= UMPanel::textLabel($layout, $p4, 31);
        return $panelTitle . $xml;
    }

    static function qualificationPoints(Layout $layout, UMConfig $umConfig) {
        $raceDist = is_array($umConfig->um4QualiBestRace->pointsDistribution)
            ? $umConfig->um4QualiBestRace->pointsDistribution
            : array();

        $lapDist = is_array($umConfig->um4QualiBestLap->pointsDistribution)
            ? $umConfig->um4QualiBestLap->pointsDistribution
            : array();

        // Keep table out of the submenu area.
        $reservedForSubmenu = 17.0 + 0.8; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 0.0;
        $tableTopGap = 3.2; // how far below the subheader the table starts (tweak to taste)

        $xml = UMPanel::textLabel($layout, '$fffPoints Per Map in Qualification', $subHeaderYOffset, true);

        $columns = array(
            array('header' => 'Rank', 'data' => array(1,2,3,4,5,6,7,8,9,10,11,12), 'halign' => 'left'),
            array('header' => 'Best Race', 'data' => $raceDist, 'halign' => 'right'),
            array('header' => 'Best Lap', 'data' => $lapDist, 'halign' => 'right')
        );

        $columnsForTable1 = $columns;
        $columnsForTable1[1]['data'] = array_slice($raceDist, 0, 12);
        $xml .= TableBuilder::build(
            $layout,
            12,
            $layout->geometry->panelWidth / 2 + $reservedForSubmenu / 2,
            $tableTopGap,
            0.0,
            $columnsForTable1
        );

        $columnsForTable2 = $columns;
        $columnsForTable2[0]['data'] = array(13,14,15,16,17,18,19,20,21,22,23,24);
        $columnsForTable2[1]['data'] = array_slice($raceDist, 12);
        $xml .= TableBuilder::build(
            $layout,
            12,
            $layout->geometry->panelWidth / 2 + $reservedForSubmenu / 2,
            $tableTopGap,
            $layout->geometry->panelWidth / 2 - $reservedForSubmenu / 2,
            $columnsForTable2
        );
        $panelTitle = RightPanel::buildTitle($layout, 'Rules: Qualification Points');
        return $panelTitle . $xml;
    }

    static function semiFinal(Layout $layout, UMConfig $umConfig) {
        $raceDist = $umConfig->um4Semi->pointsDistribution;

        // Keep table out of the submenu area.
        $reservedForSubmenu = 17.0 + 0.8; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 7.0;
        $tableTopGap = 13.5; // how far below the subheader the table starts (tweak to taste)
        $accentColor = $layout->theme->accentTextColor;
        $p1 = "The top 24 players from the qualification have a spot in the semi-final. "
            . "Of these, the 12 best players will advance to the grand-final. "
            . "At this stage, points do {$accentColor}NOT \$fffcarry-over to the grand-final.";
        $xml = UMPanel::textLabel($layout, $p1);

        $xml .= UMPanel::textLabel($layout, '$fffMaps and Stints', $subHeaderYOffset, true);

        $xml .= TableBuilder::build(
            $layout,
            1,
            $reservedForSubmenu,
            $subHeaderYOffset + 3,
            0.0,
            array(
                array('header' => 'Maps', 'data' => array(7), 'halign' => 'left'),
                array('header' => 'Stints Per Map', 'data' => array(2), 'halign' => 'right'),
                array('header' => 'Laps Per Stint', 'data' => array(6), 'halign' => 'right'),
            )
        );


        $xml .= UMPanel::textLabel($layout, '$fffPoints Per Stint in Semi-final', $subHeaderYOffset + 9, true);

        $columns = array(
            array('header' => 'Rank', 'data' => array(1,2,3,4,5,6,7,8,9,10,11,12), 'halign' => 'left'),
            array('header' => 'Points', 'data' => $raceDist, 'halign' => 'right'),
        );


        $columnsForTable1 = $columns;
        $columnsForTable1[1]['data'] = array_slice($raceDist, 0, 12);
        $xml .= TableBuilder::build(
            $layout,
            12,
            $layout->geometry->panelWidth / 2 + $reservedForSubmenu / 2,
            $subHeaderYOffset + 12,
            0.0,
            $columnsForTable1
        );

        $columnsForTable2 = $columns;
        $columnsForTable2[0]['data'] = array(13,14,15,16,17,18,19,20,21,22,23,24);
        $columnsForTable2[1]['data'] = array_slice($raceDist, 12);
        $xml .= TableBuilder::build(
            $layout,
            12,
            $layout->geometry->panelWidth / 2 + $reservedForSubmenu / 2,
            $subHeaderYOffset + 12,
            $layout->geometry->panelWidth / 2 - $reservedForSubmenu / 2,
            $columnsForTable2
        );

        $panelTitle = RightPanel::buildTitle($layout, 'Rules: Semi-Final');
        return $panelTitle . $xml;
    }
    static function grandFinal(Layout $layout, UMConfig $umConfig) {
        $raceDist = $umConfig->um4GF->pointsDistribution;

        // Keep table out of the submenu area.
        $reservedForSubmenu = 17.0 + 0.8; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 7.0;
        $tableTopGap = 10.5; // how far below the subheader the table starts (tweak to taste)

        $p1 = "The top 12 players of the semi-final advance to the grand-final. "
            . "Points do {$layout->theme->accentTextColor}NOT \$fffcarry-over to the grand-final.";
        $xml = UMPanel::textLabel($layout, $p1);

        $xml .= UMPanel::textLabel($layout, '$fffMaps and Stints', 5, true);

        $xml .= TableBuilder::build(
            $layout,
            1,
            $reservedForSubmenu,
            8,
            0.0,
            array(
                array('header' => 'Maps', 'data' => array(7), 'halign' => 'left'),
                array('header' => 'Stints Per Map', 'data' => array(3), 'halign' => 'right'),
                array('header' => 'Laps Per Stint', 'data' => array(8), 'halign' => 'right'),
            )
        );

        $xml .= UMPanel::textLabel($layout, '$fffPoints Per Stint in Grand-Final', 14, true);

        $columns = array(
            array('header' => 'Rank', 'rank' => true, 'halign' => 'left'),
            array('header' => 'Points', 'data' => $raceDist, 'halign' => 'right'),
        );

        $xml .= TableBuilder::build(
            $layout,
            12,
            $reservedForSubmenu,
            17,
            0.0,
            $columns,
            1.4
        );


        $panelTitle = RightPanel::buildTitle($layout, 'Rules: Grand-Final');
        return $panelTitle . $xml;
    }
}