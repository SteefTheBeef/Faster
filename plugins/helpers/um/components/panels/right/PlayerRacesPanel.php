<?php


class PlayerRacesPanel {
    static function build($login, $selectedPlayer, Layout $layout, array $mlAct) {
        if (!is_array($selectedPlayer) || !isset($selectedPlayer['Races']) || !is_array($selectedPlayer['Races']) || count($selectedPlayer['Races']) < 1) {
            return UMPanel::textLabel($layout, 'Select a player on the left...');
        }

        $races = $selectedPlayer['Races'];

        $v = self::computeRacesTableLayout($layout);

        $page = (int)MLState::mlGet($login, UmPanelKeys::ML_RACES_PAGE, 0);
        $pageCount = UMPanel::racesPageCount($races);
        $page = UMPanel::clampInt($page, 0, $pageCount - 1);
        MLState::mlSet($login, UmPanelKeys::ML_RACES_PAGE, $page);

        $racesToShow = UMPanel::racesSliceForPage($races, $page);

        $rows = array();
        $i = 0;
        foreach ($racesToShow as $race) {
            if (is_array($race)) {
                $rows[] = self::formatRaceRow($race, $i);
            }
            $i++;
        }

        $showPager = (is_array($races) && count($races) > RACES_PER_PAGE);
        $canPrev = false;
        $canNext = false;

        if ($showPager) {
            $canPrev = ($page > 0);
            $canNext = ($page < $pageCount - 1);
        }

        return RightPanel::buildTitle($layout, $selectedPlayer['NickNameWithColor']) . self::renderRacesTable($v, $rows, $page, $pageCount, $showPager, $mlAct, $canPrev, $canNext);
    }

    /**
     * Geometry/layout-only: compute all column widths / x positions / constants for the races table.
     *
     * @param Layout $layout
     * @return array<string, float|int|string>
     */
    private static function computeRacesTableLayout(Layout $layout) {
        $panelW = $layout->geometry->panelWidth;
        $topY = $layout->geometry->panelBodyTopY;

        $contentL = 1.2;
        $contentR = 1.2;

        $gutter = 0.5;
        $gutterAfterIdx = 0.9;

        $idxW = 3.0;

        $usableW = $panelW - $contentL - $contentR - (3 * $gutter) - $gutterAfterIdx;
        if ($usableW < 0) $usableW = 0;

        if ($idxW > $usableW) $idxW = $usableW;

        $otherW = $usableW - $idxW;
        if ($otherW < 0) $otherW = 0;

        $colW = $otherW / 4.0;

        $idxPadL = 0.4;
        $timePadR = $idxPadL;

        $xIdxLeft = $contentL + $idxPadL;
        $xEnv = $contentL + $idxW + $gutterAfterIdx;
        $xRank = $xEnv + $colW + $gutter;
        $xPts = $xRank + $colW + $gutter;
        $xTimeRight = $panelW - $contentR - $timePadR;

        $tableX = $contentL;
        $tableW = $panelW - $contentL - $contentR;
        if ($tableW < 0) $tableW = 0;

        $rowH = 2.4;
        $headerY = $topY;

        return array(
            'panelW' => $panelW,
            'topY' => $topY,

            'contentL' => $contentL,
            'contentR' => $contentR,

            'gutter' => $gutter,
            'gutterAfterIdx' => $gutterAfterIdx,

            'idxW' => $idxW,
            'colW' => $colW,

            'idxPadL' => $idxPadL,
            'timePadR' => $timePadR,

            'xIdxLeft' => $xIdxLeft,
            'xEnv' => $xEnv,
            'xRank' => $xRank,
            'xPts' => $xPts,
            'xTimeRight' => $xTimeRight,

            'tableX' => $tableX,
            'tableW' => $tableW,

            'rowH' => $rowH,
            'headerY' => $headerY,
        );
    }

    /**
     * Data-only: normalize a raw $race array into fields the renderer can consume.
     *
     * @param array $race
     * @param int $fallbackIndex 0-based index in the visible page
     * @return array<string, mixed>
     */
    private static function formatRaceRow(array $race, $fallbackIndex) {
        $raceIdx = isset($race['RaceIndex'])
            ? (string)(((int)$race['RaceIndex']) + 1)
            : (string)(((int)$fallbackIndex) + 1);

        $env = '';
        if (isset($race['RaceInfo']) && is_array($race['RaceInfo']) && isset($race['RaceInfo']['Environment'])) {
            $env = (string)$race['RaceInfo']['Environment'];
        }

        $rank = isset($race['Rank']) ? (string)$race['Rank'] : '';
        $rankInt = (int)$rank;

        $time = '';
        if (isset($race['Score']) && is_array($race['Score'])) {
            if (isset($race['Score']['Time']) && $race['Score']['Time'] !== '') {
                $time = (string)$race['Score']['Time'];
            } elseif (isset($race['Score']['RaceTime']) && $race['Score']['RaceTime'] !== '') {
                $time = (string)$race['Score']['RaceTime'];
            }
        }

        $pts = isset($race['AwardedPoints']) ? (string)$race['AwardedPoints'] : '';

        return array(
            'idx' => $raceIdx,
            'env' => $env,
            'rank' => $rank,
            'rankInt' => $rankInt,
            'pts' => $pts,
            'time' => $time,
        );
    }

    /**
     * XML-only: emit the races table XML given layout vars + normalized rows.
     *
     * @param array $v Layout vars from computeRacesTableLayout()
     * @param array<int, array<string,mixed>> $rows Normalized rows from formatRaceRow()
     * @param int $page 0-based
     * @param int $pageCount
     * @param bool $showPager
     * @param array $mlAct
     * @param bool $canPrev
     * @param bool $canNext
     * @return string
     */
    private static function renderRacesTable(array $v, array $rows, $page, $pageCount, $showPager, array $mlAct, $canPrev, $canNext) {
        $xml = '';

        $headerFont = '$cf0$o';
        $xml .= XmlTag::quad($v['tableX'], $v['headerY'], $v['tableW'], $v['rowH'], '0006');
        $xml .= XmlTag::label($v['xIdxLeft'], $v['headerY'] - 0.6, $v['idxW'] - $v['idxPadL'], $v['rowH'], $headerFont . '#');
        $xml .= XmlTag::label($v['xEnv'], $v['headerY'] - 0.6, $v['colW'], $v['rowH'], $headerFont . 'Environment');
        $xml .= XmlTag::labelRight($v['xRank'], $v['headerY'] - 0.6, $v['colW'], $v['rowH'], $headerFont . 'Rank');
        $xml .= XmlTag::labelRight($v['xPts'], $v['headerY'] - 0.6, $v['colW'], $v['rowH'], $headerFont . 'Points');
        $xml .= XmlTag::labelRight($v['xTimeRight'], $v['headerY'] - 0.6, $v['colW'] - $v['timePadR'], $v['rowH'], $headerFont . 'Time');

        $enviFont = '$390$o';

        $i = 0;
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            $row = $rows[$i];

            $rowY = $v['headerY'] - (($i + 1) * $v['rowH']);
            $bg = (($i % 2) === 0) ? '0003' : '0000';

            $rankInt = isset($row['rankInt']) ? (int)$row['rankInt'] : 0;
            $otherFont = ($rankInt > 3) ? '$fff$o' : '$fc0$o';

            $xml .= XmlTag::quad($v['tableX'], $rowY, $v['tableW'], $v['rowH'], $bg);
            $xml .= XmlTag::label($v['xIdxLeft'], $rowY - 0.6, $v['idxW'] - $v['idxPadL'], $v['rowH'], $otherFont . (string)$row['idx']);
            $xml .= XmlTag::label($v['xEnv'], $rowY - 0.6, $v['colW'], $v['rowH'], $enviFont . (string)$row['env']);
            $xml .= XmlTag::labelRight($v['xRank'], $rowY - 0.6, $v['colW'], $v['rowH'], $otherFont . (string)$row['rank']);
            $xml .= XmlTag::labelRight($v['xPts'], $rowY - 0.6, $v['colW'], $v['rowH'], $otherFont . (string)$row['pts']);
            $xml .= XmlTag::labelRight($v['xTimeRight'], $rowY - 0.6, $v['colW'] - $v['timePadR'], $v['rowH'], $otherFont . (string)$row['time']);
        }

        if ($showPager) {
            $pagerY = $v['headerY'] - (($count + 1) * $v['rowH']) - 1.2;

            $prevAct = ($canPrev && isset($mlAct[UmPanelKeys::ACT_RACES_PREV])) ? (int)$mlAct[UmPanelKeys::ACT_RACES_PREV] : null;
            $nextAct = ($canNext && isset($mlAct[UmPanelKeys::ACT_RACES_NEXT])) ? (int)$mlAct[UmPanelKeys::ACT_RACES_NEXT] : null;

            $xml .= XmlTag::pagerPrevNext64(
                $v['tableX'],
                $pagerY,
                0.2,
                $v['tableW'],
                (int)$page,
                (int)$pageCount,
                $prevAct,
                $nextAct
            );
        }
        return $xml;
    }
}