<?php
require_once __DIR__ . '/../../utils/StringUtils.php';
require_once __DIR__ . '/../UMPanel.php';
require_once __DIR__ . '/../layout/Layout.php';
require_once 'TableBuilder.php';

class RulesPanelBuilder {
    static function build($login, Layout $layout, $umConfig) {
        global $_players;

        $submenuItems = array(
            array('title' => 'Qualification',         'action' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION),
            array('title' => 'Qualification Points',  'action' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION_POINTS),
            array('title' => 'Semi-Final',            'action' => UmPanelKeys::ACT_SUBTAB_RULES_SEMI_FINAL),
            array('title' => 'Grand-Final',           'action' => UmPanelKeys::ACT_SUBTAB_RULES_MISC),
        );

        $sub = SubMenuBuilder::build(
            $login,
            $layout,
            UmPanelKeys::ACT_TAB_RULES,
            $submenuItems,
            array(
                'submenuR' => 0.0,
                'submenuW' => 17.0,
                'gap'      => 0.0,

                // NEW: let the builder validate+default
                'defaultAction' => UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION,
            )
        );

        switch ($sub['activeAction']) {
            case UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION:
                $contentXml = self::qualification($layout, $umConfig);
                break;

            case UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION_POINTS:
                $contentXml = self::qualificationPoints($layout, $umConfig);
                break;

            case UmPanelKeys::ACT_SUBTAB_RULES_SEMI_FINAL:
                $contentXml = self::semiFinalPoints($layout, $umConfig);
                break;

            default:
                $contentXml = UMPanel::textLabel($layout, "Misc rules...\n(Replace this with your real content)");
                break;
        }

        return $contentXml . $sub['xml'];
    }

    static function qualification(Layout $layout, $umConfig) {
        $xml = UMPanel::textLabel($layout, '$fffQualification', 0, true);
        $accentColor = $layout->theme->accentTextColor;

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
        return $xml;
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
            array('header' => 'Rank', 'rank' => true, 'halign' => 'left'),
            array('header' => 'Points Fastest Race', 'data' => $raceDist, 'halign' => 'right'),
        );

        // Optional 3rd column: only add it if you actually provide data
        if (is_array($lapDist) && count($lapDist) > 0) {
            $columns[] = array('header' => 'Points Fastest Lap', 'data' => $lapDist, 'halign' => 'right');
        }

        $xml .= TableBuilder::build(
            $layout,
            24,
            $reservedForSubmenu,
            $tableTopGap,
            $columns
        );

        return $xml;
    }

    static function semiFinalPoints(Layout $layout, UMConfig $umConfig) {
        $raceDist = is_array($umConfig->um4Semi->pointsDistribution)
            ? $umConfig->um4QualiBestRace->pointsDistribution
            : array();

        // Keep table out of the submenu area.
        $reservedForSubmenu = 17.0 + 0.8; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 7.0;
        $tableTopGap = 10.5; // how far below the subheader the table starts (tweak to taste)

        $p1 = "The top 24 players from the qualification have a spot in the semi-final. "
            . "Of these, the 12 best players will advance to the grand-final. "
            . "At this stage, points do {$layout->theme->accentTextColor}NOT \$fffcarry-over to the grand-final.";

        $xml = UMPanel::textLabel($layout, $p1);
        $xml .= UMPanel::textLabel($layout, '$fffPoints Per Map in Semi-final', $subHeaderYOffset, true);

        $columns = array(
            array('header' => 'Rank', 'rank' => true, 'halign' => 'left'),
            array('header' => 'Points Fastest Race', 'data' => $raceDist, 'halign' => 'right'),
        );

        $xml .= TableBuilder::build(
            $layout,
            24,
            $reservedForSubmenu,
            $tableTopGap,
            $columns,
            1.4
        );

        return $xml;
    }
}