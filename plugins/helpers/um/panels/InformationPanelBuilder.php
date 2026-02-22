<?php
require_once __DIR__ . '/../../utils/StringUtils.php';
require_once __DIR__ . '/../UMPanel.php';

class InformationPanelBuilder {
    static function getInformationPanel($layout) {
        $p1 = "\$fffRace against the greatest legends of Trackmania United Forever."
            . " This cup is not just a contest of skill, but truly a battle of wills and perseverance."
            . " Who will claim the right to call themselves a grand master?";
        $xml = UMPanel::textLabel($layout, $p1);

        $xml .= UMPanel::textLabel($layout, '$fffWhat is United Masters?', 9, true);

        $pl2 = "\$fffUnited Masters is a cup that is driven in \$390laps mode\$fff."
            . " To advance to the playoffs you need to compete in the qualification phase.";
        $xml .= UMPanel::textLabel($layout, $pl2, 12);

        $pl3 = "\$fffThe qualification begins \$390Sunday 20:00 CET March 1st \$fffand ends \$390March 12th 23:00 CET."
            . "\$fff Race for 4 laps on 7 maps, one map for each of the TMUF Environments and the cumulative points of the top 24 players guarantees a spot in playoffs!";
        $xml .= UMPanel::textLabel($layout, $pl3, 18);

        $pl4 = "\$fffPlayoffs will start with the semi-final being played on Sunday March 15, 20:30 CET."
            . "This is a live event where you drive against other players in head-to-head battle for the chance to play in the glorious grand-final!."
            . "The grand-final is played over two Sundays, March 29 and April 5, 20:30 CET.";
        $xml .= UMPanel::textLabel($layout, $pl4, 28);


        $discordLink = "\$fff\$oJoin us on our discord at \$f09\$lhttps://discord.gg/457Bxpf";

        //$xml .= UMPanel::textLabel($layout, $discordLink, 17);

        //$xml .= "<label posn='1" . ($panelBodyTopY - 10) . " 0.2' sizen='" . ($panelW/1.5 - 2) . " {$panelBodyH}' halign='left' valign='top' textsize='1' autonewline='1' text='" . safeString($discordLink) . "'/>";
        return $xml;
    }
}