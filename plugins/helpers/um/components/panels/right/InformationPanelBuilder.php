<?php

class InformationPanelBuilder {
    static function getInformationPanel(Layout $layout) {
        $accentColor = $layout->theme->accentTextColor;
        $p1 = "\$fffTake on the greatest legends in Trackmania United Forever and claim what's rightfully yours from the {$accentColor}\$o1200 EUR guaranteed prize pool!\$o\$fff"
            . " This cup is not just a contest of skill, but truly a battle of wills and perseverance."
            . " Who will claim the right to call themselves a grand master?";
        $xml = UMPanel::textLabel($layout, $p1);
        $addToOffset = 1.5;
        $xml .= UMPanel::textLabel($layout, '$fffWhat is United Masters?', 9 + $addToOffset, true);

        $pl2 = "\$fffUnited Masters is a cup that is driven in {$accentColor}laps mode\$fff."
            . " To advance to the playoffs you need to compete in the qualification phase.";
        $xml .= UMPanel::textLabel($layout, $pl2, 12 + $addToOffset);

        $pl3 = "\$fffThe qualification begins {$accentColor}Sunday 20:00 CET March 1st \$fffand ends {$accentColor}Thursday March 12th 23:00 CET."
            . "\$fff Race for 4 laps on 7 maps, one map for each of the TMUF Environments and the cumulative points of the top 24 players guarantees a spot in playoffs!";
        $xml .= UMPanel::textLabel($layout, $pl3, 18 + $addToOffset);

        $pl4 = "\$fffPlayoffs will start with the semi-final being played on {$accentColor}Sunday March 15, 20:30 CET. "
            . "\$fffThis is a live event where you drive against other players in head-to-head battle for the chance to play in the glorious grand-final. "
            . "The grand-final is played over two Sundays, {$accentColor}March 29 \$fffand {$accentColor}April 5, 20:30 CET.";
        $xml .= UMPanel::textLabel($layout, $pl4, 28 + $addToOffset);


        $discordLink = "\$fffJoin us on our discord at \$f09\$lhttps://discord.gg/457Bxpf";

        $xml .= UMPanel::textLabel($layout, $discordLink, 39 + $addToOffset, true, array('autonewline' => '0'));

        //$xml .= "<label posn='1" . ($panelBodyTopY - 10) . " 0.2' sizen='" . ($panelW/1.5 - 2) . " {$panelBodyH}' halign='left' valign='top' textsize='1' autonewline='1' text='" . safeString($discordLink) . "'/>";
        return RightPanel::buildTitle($layout, "Information") . $xml;
    }
}