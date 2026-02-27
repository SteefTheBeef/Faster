<?php

class UmState {
    public $qualificationBestRacesRanking = array();
    public $qualificationBestLapsRanking = array();

    public $qualificationRankingsPerEnv = array();
    public $qualificationRankings = array();

    public $qualiConfigBestRaces;
    public $qualiConfigBestLaps;

    public $selectedPlayerCollection = array();


    public $players = array();

    public $selectedTab = array();
    public $selectedSubTab = array();

    public $selectedPlayerPaginationIndex = array();
    public $selectedPlayerIndex = array();
    public $selectedPlayer = array();
    public $boardIsOpen = array();

    public function __construct($qualiConfigBestRaces, $qualiConfigBestLaps) {
        $this->qualificationBestRacesRanking = array();
        $this->qualificationBestLapsRanking = array();
        $this->qualiConfigBestRaces = $qualiConfigBestRaces;
        $this->qualiConfigBestLaps = $qualiConfigBestLaps;
    }

    public function computeRankings() {
        $this->players = UmPlayers::loadPlayersNicknamesMap();
        $this->qualificationBestRacesRanking = BestRaces::buildQualificationRankingsAllMaps($this->qualiConfigBestRaces, $this->players);
        $this->qualificationBestLapsRanking = BestRaces::buildQualificationRankingsAllMapsBestLaps($this->qualiConfigBestLaps, $this->players);
        $this->qualificationRankingsPerEnv = QualificationRankingService::mergeQualificationScoresByEnv($this->qualificationBestRacesRanking, $this->qualificationBestLapsRanking);
        $this->qualificationRankings = QualificationRankingService::buildQualificationLeaderboardAllEnvs($this->qualificationRankingsPerEnv);

        foreach ($this->boardIsOpen as $login => $isOpen) {
            $this->setSelectedTab($login, $this->getSelectedTab($login), $this->getSelectedSubTab($login));
        }
    }

    public function playerConnect($login) {
        $this->selectedPlayerCollection[$login] = $this->qualificationRankings;
        $this->selectedPlayerIndex[$login] = 0;
        $this->selectedPlayer[$login] = isset($this->selectedPlayerCollection[$login][0]) ? $this->selectedPlayerCollection[$login][0] : null;
        $this->selectedTab[$login] = UmPanelKeys::ACT_TAB_QUALIFICATION;
        $this->selectedSubTab[$login] = UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD;
        $this->boardIsOpen[$login] = true;
        $this->selectedPlayerPaginationIndex[$login] = 0;

    }

    public function playerDisconnect($login) {
        unset($this->selectedPlayerCollection[$login]);
        unset($this->selectedPlayerIndex[$login]);
        unset($this->selectedPlayer[$login]);
        unset($this->selectedTab[$login]);
        unset($this->selectedSubTab[$login]);
        unset($this->boardIsOpen[$login]);
        unset($this->selectedPlayerPaginationIndex[$login]);
    }

    public function setSelectedTab($login, $action, $subTabAction = null) {
        if ($action === UmPanelKeys::ACT_TAB_RULES) {
            $this->setSelectedSubTab($login, $subTabAction ?: UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION);
        }
        if ($action === UmPanelKeys::ACT_TAB_QUALIFICATION) {
            $this->setSelectedSubTab($login, $subTabAction ?: UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD);
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
        $this->selectedPlayerIndex[$login] = 0;
        $this->selectedPlayer[$login] = null;

        // choose appropriate player collection based on subtab
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD) {
            $this->selectedPlayerCollection[$login] = $this->qualificationRankings;
            if (isset($this->selectedPlayerCollection[$login]) && count($this->selectedPlayerCollection[$login]) > 0)
            $this->selectedPlayer[$login] = $this->selectedPlayerCollection[$login][0];
            return;
        }

        $envKey = UmPanelKeys::getQualificationEnvironmentKeyBySubtabAction($action);
        if ($envKey !== null) {
            $this->selectedPlayerCollection[$login] = isset($this->qualificationRankingsPerEnv[$envKey])
                ? $this->qualificationRankingsPerEnv[$envKey] : array();
            if (isset($this->selectedPlayerCollection[$login]) && count($this->selectedPlayerCollection[$login]) > 0) {
                $this->selectedPlayer[$login] = $this->selectedPlayerCollection[$login][0];
            }
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

    public function setSelectedPlayerIndex($login, $action) {
        $rowPrefix = UmPanelKeys::ACT_PLAYERS_SELECT;
        $rowIndex = (int)substr($action, strlen($rowPrefix));
        $this->selectedPlayerIndex[$login] = $rowIndex;

        // set selected player for convenience
        $currentIndex = $this->selectedPlayerPaginationIndex[$login];
        $newSelectedPlayer = $this->selectedPlayerCollection[$login][$currentIndex * PLAYERS_PER_PAGE + $rowIndex];
        $this->selectedPlayer[$login] = $newSelectedPlayer;
        //        if ($newSelectedPlayer === $this->selectedPlayer[$login]) {
        //            $this->selectedPlayer[$login] = null;
        //            $this->selectedPlayerIndex[$login] = null;
        //        } else {
        //            $this->selectedPlayer[$login] = $newSelectedPlayer;
        //        }
        //$this->selectedPlayer[$login] = $newSelectedPlayer;
            //console("SELECTED PLAYER!!!!: " . print_r($this->selectedPlayer[$login], true));
    }
}