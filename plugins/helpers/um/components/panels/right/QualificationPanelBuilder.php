<?php


class QualificationPanelBuilder {
    static function build($login, Layout $layout, UMConfig $umConfig, UmState $umState) {
        global $_players;

        $selectedSubTab = $umState->getSelectedSubTab($login);
        $items = array(
            array('title' => 'Leaderboard', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD),
            array('title' => 'Rally', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY),
            array('title' => 'Speed', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED),
            array('title' => 'Alpine', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE),
            array('title' => 'Coast', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST),
            array('title' => 'Island', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND),
            array('title' => 'Bay', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY),
            array('title' => 'Stadium', 'action' => UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM),
        );

        $sub = SubTabs::bottom($login, $layout, UmPanelKeys::ACT_TAB_QUALIFICATION, $items, $selectedSubTab, array(
            'placement' => 'bottom',
            'submenuR' => 0.0,
            'rowH' => 2.8,
            'bottomY' => -53.6,

            'autoWidth' => true,
            'fill' => true,   // use the unused space
            'tabLift' => 0.5,    // adjust 0.3..0.8 if needed

            'itemGap' => 0.0,
            'textsize' => 1.10,
            'tabPadLR' => 0.7,
            'tabMinW' => 3.4,
            'tabMaxW' => 40.0,
        ));

        switch ($sub['activeAction']) {
            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD:
                $contentXml = self::leaderboard($layout, $umConfig);
                break;

            case UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY:
                $contentXml = self::envi($layout, $umConfig);
                break;

            default:
                $contentXml = UMPanel::textLabel($layout, "Misc rules...\n(Replace this with your real content)");
                break;
        }

        return $contentXml . $sub['xml'];
    }

    static function leaderboard(Layout $layout, $umConfig) {
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

    static function envi(Layout $layout, UMConfig $umConfig) {
        return RightPanel::buildTitle($layout, 'Qualification: Rally');
    }
}