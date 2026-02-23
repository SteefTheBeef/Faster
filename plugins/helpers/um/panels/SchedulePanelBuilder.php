<?php
require_once __DIR__ . '/../../utils/StringUtils.php';
require_once __DIR__ . '/../UMPanel.php';
require_once __DIR__ . '/../layout/Layout.php';

class SchedulePanelBuilder {
    static function schedule(Layout $layout, UMConfig $umConfig) {
        $raceDist = is_array($umConfig->um4Semi->pointsDistribution)
            ? $umConfig->um4QualiBestRace->pointsDistribution
            : array();

        // Keep table out of the submenu area.
        $reservedForSubmenu = 0; // submenuW + breathing gap

        // Add a subheader like in the Qualification panel
        $subHeaderYOffset = 7.0;
        $tableTopGap = 3; // how far below the subheader the table starts (tweak to taste)

        $xml = UMPanel::textLabel($layout, '$fffEvents', 0,true);

        $dates = array(
            'March 1 Sunday 20:00',
            'March 12 Thursday 23:00',
            'March 15 Sunday 20:30',
            'March 15 Sunday 23:00',
            'March 29 Sunday 20:30',
            'April 5 Sunday 20:30',
            );
        $eventTypes = array(
            'Qualifications Start',
            'Qualifications End',
            'Semi-final',
            'PO Map Pack Release',
            'Grand-final Day One',
            'Grand-final Day Two',
        );

        $mapPacks = array(
            'TA Map Pack',
            '',
            'TA Map Pack',
            '',
            'PO Map Pack',
            'PO Map Pack',
        );

        $mapCounts = array(
            '7',
            '',
            '7',
            '',
            '3',
            '4',
        );

        $lapCounts = array(
            '4x1',
            '',
            '6x2',
            '',
            '8x3',
            '8x3',
        );

        $columns = array(
            array('header' => 'Date', 'data' => $dates),
            array('header' => 'Event', 'data' => $eventTypes),
            array('header' => 'Map Pack', 'data' => $mapPacks),
            array('header' => 'Map Count', 'data' => $mapCounts),
            array('header' => 'Laps x Stints', 'data' => $lapCounts),
        );

        $xml .= TableBuilder::build(
            $layout,
            6,
            $reservedForSubmenu,
            $tableTopGap,
            $columns,
            2.2
        );

        $accentColor = $layout->theme->accentTextColor;

        $xml .= UMPanel::textLabel($layout, '$fffMap Order', 20.5,true);
        $xml .= UMPanel::textLabel($layout, "{$accentColor}Semi-final", 24,true);
        $xml .= UMPanel::textLabel($layout, "Island, Rally, Desert, Snow, Bay, Coast, Stadium", 26);

        $xml .= UMPanel::textLabel($layout, "{$accentColor}Grand-final Day One", 29,true);
        $xml .= UMPanel::textLabel($layout, "Island, Desert, Stadium", 31);

        $xml .= UMPanel::textLabel($layout, "{$accentColor}Grand-final Day Two", 34,true);
        $xml .= UMPanel::textLabel($layout, "Rally, Bay, Snow, Coast", 36);

        return $xml;
    }
}