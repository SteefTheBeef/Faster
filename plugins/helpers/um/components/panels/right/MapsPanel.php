<?php

class MapsPanel {
    static function build(UmPanelRenderContext $ctx) {


        $geometry = $ctx->layout->geometry;
        $xml = '';
        $y = -4;
        $bgQuad = XmlTag::quad(0, 0, $geometry->panelWidth, $geometry->panelHeight, $ctx->layout->theme->tabActiveBackgroundColor, null, array('z' => 0.45));
        //$xml .= XmlTag::label(1, $y, 30, 2.5, '$o' . $i . '$o');

        $avgMapRatings = array();
        foreach ($ctx->umConfig->um4QualiBestRace->maps as $map) {
            $avgMapRatings[$map->id] = $map->toRatingsArray();
        }

        foreach ($ctx->umState->mapRatingsTA as $login => &$playerRatings) {
            foreach ($playerRatings as $mapId => &$mapRating) {

                $avgMapRatings[$mapId]['TotalRanks'] = $avgMapRatings[$mapId]['TotalRanks'] + $mapRating['Rank'] + 1;
                $avgMapRatings[$mapId]['RanksCount'] += 1;
                $avgMapRatings[$mapId]['Rank'] = (float)($avgMapRatings[$mapId]['TotalRanks'] / $avgMapRatings[$mapId]['RanksCount']);
            }
        }

        MapRatingsService::sortRatingsForLoginByRank($avgMapRatings);
        $xml .= XmlTag::label(1, $y, 30, 2.5, '$o$cf0' . 'Average TA Map Pack Ratings' . '$o');
        $y -= 2.5;
        $xml .= self::drawTable($avgMapRatings, $ctx, $y);
        //Your Current TA Map Pack Ratings
        $y += 0.5;
        $xml .= XmlTag::label(1, $y, 40, 2.5,  'Average rating results of ' . count($ctx->umState->mapRatingsTA) . ' players. Lower avg is better.');
        $y -= 3.5;
        $xml .= XmlTag::label(1, $y, 40, 2.5, '$o$cf0' . 'Rank Your Favorite Maps from the TA Map Pack' . '$o');
        $y -= 2.5;
        $ratingsForLogin = MapRatingsService::getRatingForLogin($ctx->login, $ctx->umState->mapRatingsTA, $ctx->umConfig->um4QualiBestRace->maps);
        $xml .= self::drawTable($ratingsForLogin, $ctx, $y, true);
        return RightPanel::buildTitle($ctx->layout, 'Map Pack Ratings') . $xml;
    }

    static function drawTable($ratings, $ctx, &$y = -4, $canVote = false) {
        $i = 1;
        $isDefaultValues = false;
        $xml = '';

        foreach ($ratings as $rating) {
            $actionIndex = $i - 1;
            if (isset($rating['Default'])) {
                $isDefaultValues = true;
            }

            $numberFont = $i > 3 ? '$fff': $ctx->layout->theme->goldColor;
            $rank = $canVote ? $numberFont . $i : $numberFont . $i . ' $fff(' . number_format((float)$rating['Rank'], 2, '.', '') . ')';
            $xml .= $canVote ? XmlTag::quadIcon64(1, $y, 1.6, 'ArrowUp',   $actionIndex > 0 ? $ctx->mlAct[UmPanelKeys::ACT_RATE_MAP_UP . '.' .  $actionIndex] : null, array('z' => 0.46)) : '';
            $xml .= XmlTag::label($canVote ? 3 : 1, $y, 30, 2.5, '$o' . $rank . '$o');
            $xml .= $canVote ? XmlTag::quadIcon64(4.5, $y, 1.6, 'ArrowDown', $actionIndex < 6 ? $ctx->mlAct[UmPanelKeys::ACT_RATE_MAP_DOWN . '.' .  $actionIndex] : null, array('z' => 0.46)) : '';

            $name = UMPanel::mlStripItalics(UMPanel::mlStripBold($rating['Name']));
            $xml .= XmlTag::label(7, $y, 30, 2.5, '$o' . $name . ' $fff(' . $rating['Environment'] .')' . '$o', null, array('z' => 0.46));
            $y = $y - 2.5;
            $i++;
        }

        $font = $ctx->layout->theme->accentTextColor;
        $defaultValuesText = "These are default values and not saved. "
            . " To save your ratings you need to cast at least one vote (up or down).";

        $xml .= $isDefaultValues ? XmlTag::label(1, $y, 34, 5, $defaultValuesText) : '';

        return $xml;
        //return array('xml' => $xml, 'y' => $y);
    }

}