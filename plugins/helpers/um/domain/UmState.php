<?php

class UmState {
    public $shouldUpdateXml = true;
    public $shouldComputeRankings = true;
    public $qualificationBestRacesRanking = array();
    public $qualificationBestLapsRanking = array();

    public $qualificationRankingsPerEnv = array();
    public $qualificationRankings = array();

    public $qualiConfigBestRaces;
    public $qualiConfigBestLaps;

    public $selectedPlayerCollection = array();
    public $selectedPlayerRowIndex = array();

    public $players = array();

    public $selectedTab = array();
    public $selectedSubTab = array();

    public $selectedPlayerPaginationIndex = array();

    public $boardIsOpen = array();

    public function __construct($qualiConfigBestRaces, $qualiConfigBestLaps) {
        $this->qualificationBestRacesRanking = array();
        $this->qualificationBestLapsRanking = array();
        $this->shouldUpdateXml = true;
        $this->shouldComputeRankings = true;
        $this->qualiConfigBestRaces = $qualiConfigBestRaces;
        $this->qualiConfigBestLaps = $qualiConfigBestLaps;
    }

    public function computeRankings() {
        //console("COMPUTING NEW RANKINGS!!!!");

        if (!$this->shouldComputeRankings) return;

        $this->players = UmPlayers::loadPlayersNicknamesMap();
        $this->qualificationBestRacesRanking = BestRaces::buildQualificationRankingsAllMaps($this->qualiConfigBestRaces, $this->players);
        $this->qualificationBestLapsRanking = BestRaces::buildQualificationRankingsAllMapsBestLaps($this->qualiConfigBestLaps, $this->players);
        $this->qualificationRankingsPerEnv = QualificationRankingService::mergeQualificationScoresByEnv($this->qualificationBestRacesRanking, $this->qualificationBestLapsRanking);
        $this->qualificationRankings = QualificationRankingService::buildQualificationLeaderboardAllEnvs($this->qualificationRankingsPerEnv);
        //console("COMPUTED NEW RANKINGS");
        //console(print_r($this->qualificationBestRacesRanking, true));
        //console(print_r($this->qualificationRankingsPerEnv, true));
        console("LEADERBOARD RANKINGS:");
        console(print_r($this->qualificationRankings, true));
        $this->shouldComputeRankings = false;
    }

    public function playerConnect($login) {
        $this->selectedPlayerCollection[$login] = &$this->qualificationRankings;
        $this->selectedPlayerRowIndex[$login] = 0;
        $this->selectedTab[$login] = UmPanelKeys::ACT_TAB_QUALIFICATION;
        $this->selectedSubTab[$login] = UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD;
        $this->boardIsOpen[$login] = true;
        $this->selectedPlayerPaginationIndex[$login] = 0;
    }

    public function setSelectedTab($login, $action) {
        if ($action === UmPanelKeys::ACT_TAB_RULES) {
            $this->setSelectedSubTab($login, UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION);
        }
        if ($action === UmPanelKeys::ACT_TAB_QUALIFICATION) {
            $this->setSelectedSubTab($login, UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD);
        }
        $this->selectedTab[$login] = $action;
    }

    public function getSelectedTab($login) {
        return isset($this->selectedTab[$login]) ? $this->selectedTab[$login] : UmPanelKeys::ACT_TAB_QUALIFICATION;
    }

    public function setSelectedSubTab($login, $action) {
        $this->selectedSubTab[$login] = $action;
        // reset players page if user change subtab
        $this->selectedPlayerPaginationIndex[$login] = 0;

        // choose appropriate player collection based on subtab
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD) {
            $this->selectedPlayerCollection[$login] = $this->qualificationRankings;
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_RALLY) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Rally'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_SPEED) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Speed'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ALPINE) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Alpine'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_BAY) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Bay'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_COAST) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Coast'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_ISLAND) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Island'];
        }
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_STADIUM) {
            $this->selectedPlayerCollection[$login] = $this->qualificationBestRacesRanking['Stadium'];
        }
    }

    public function getSelectedSubTab($login) {
        return isset($this->selectedSubTab[$login]) ? $this->selectedSubTab[$login] : UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD;
    }

    public function setSelectedPlayerPaginationIndex($login, $action) {
        $currentIndex = $this->selectedPlayerPaginationIndex[$login];

        $pageCount = UMPanel::playersPageCount($this->selectedPlayerCollection[$login]);

        if ($action === UmPanelKeys::ACT_PLAYERS_PREV) $currentIndex--;
        if ($action === UmPanelKeys::ACT_PLAYERS_NEXT) $currentIndex++;

        $newIndex = UMPanel::clampInt($currentIndex, 0, $pageCount - 1);
        $this->selectedPlayerPaginationIndex[$login] = $newIndex;
        return true;
    }

    public function getSelectedPlayerPaginationIndex($login) {
        return (int)$this->selectedPlayerPaginationIndex[$login];
    }
}