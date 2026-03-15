<?php
class SemiFinalRankingService {
    static function mergeQualificationScores(&$semiFinalRankingsFromMatchlog, $qualificationRankings) {
        $rankings = array();
        $index = 0;
        foreach($qualificationRankings as $ranking) {
            $rankings[] = $ranking;
            if ($index == 23) {
                break;
            }
            $index++;
        }

        // withdrawals
        $garf = Arrays::find($rankings, 'Login', 'kvaccaro', true);
        array_splice($rankings, $garf['index'], 1);
        $drim = Arrays::find($rankings, 'Login', 'drimisback', true);
        array_splice($rankings, $drim['index'], 1);


        $kent = Arrays::find($qualificationRankings, 'Login', 'kent1');
        $gdmentos = Arrays::find($qualificationRankings, 'Login', 'gdmentos');

        $rankings[] = $kent;
        $rankings[] = $gdmentos;

        foreach($rankings as &$ranking) {
            $semiFinalRanking = Arrays::find($semiFinalRankingsFromMatchlog, 'Login', $ranking['Login']);
            $ranking['QualificationScore'] = $ranking['Score'];
            if (isset($semiFinalRanking)) {
                $ranking['SemiFinalScore'] = $semiFinalRanking['Score'];
                $ranking['Score'] = $ranking['SemiFinalScore'] + $ranking['QualificationScore'];
                $ranking['Races'] = $semiFinalRanking['Races'];
            }
        }

        // Sort by total Points desc, tie-breaker by NickName asc (stable-ish)
        usort($rankings, 'sortByPointsDescThenNameAsc');
        return $rankings;
    }
}