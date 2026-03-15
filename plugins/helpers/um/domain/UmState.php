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
    public $boardMiniIsOpen = array();

    public $miniScoreBoardIsOpen = false;

    public $mapRatingsTA = array();

    public $disconnectedLoginsToUnsetAfterRound = array();

    // SEMI-FINAL
    public $semiFinalRaces = array();
    public $semiFinalRankings = array();

    public $selectedSemiFinalRace = array();
    public $selectedSemiFinalPlayer = array();
    public $selectedSemiFinalPlayerPaginationIndex = array();
    public $selectedSemiFinalPlayerIndex = array();

    public $prevTab = array();
    public $prevSubTab = array();
    public $prizePool;
    public $umConfig;

    public function __construct(UMConfig $umConfig) {
        $this->umConfig = $umConfig;
        $this->qualificationBestRacesRanking = array();
        $this->qualificationBestLapsRanking = array();
        $this->prizePool = new PrizePool();
    }

    public function computeRankings() {
        $this->players = UmPlayers::loadPlayersNicknamesMap();
        $this->qualificationBestRacesRanking = BestRaces::buildQualificationRankingsAllMaps($this->umConfig->um4QualiBestRace, $this->players);
        $this->qualificationBestLapsRanking = BestRaces::buildQualificationRankingsAllMapsBestLaps($this->umConfig->um4QualiBestLap, $this->players);
        $this->qualificationRankingsPerEnv = QualificationRankingService::mergeQualificationScoresByEnv($this->qualificationBestRacesRanking, $this->qualificationBestLapsRanking);
        $this->qualificationRankings = QualificationRankingService::buildQualificationLeaderboardAllEnvs($this->qualificationRankingsPerEnv);
        $this->semiFinalRaces = MatchlogFileParser::parseMatchlogFile('fastlog/um/matchlog.txt');
        $semiFinalRankingsFromMatchlog = MatchlogFileParser::getScoreboardPlayersFromMatchlog('fastlog/um/matchlog.txt', $this->umConfig->um4Semi->pointsDistribution);
        $this->semiFinalRankings = SemiFinalRankingService::mergeQualificationScores($semiFinalRankingsFromMatchlog, $this->qualificationRankings);
        //console(print_r($this->semiFinalRankings, true));
        foreach ($this->boardIsOpen as $login => $isOpen) {
            $this->setSelectedTab($login, $this->getSelectedTab($login), $this->getSelectedSubTab($login));
        }
        if (isset($this->mapRatingsTA) && count($this->mapRatingsTA) > 1) {
            MapRatings::saveRatingsToFile($this->mapRatingsTA);
        }

        $this->mapRatingsTA = MapRatings::getTARatings();
    }

    public function playerConnect($login) {
        $this->selectedPlayerIndex[$login] = 0;

        $this->selectedTab[$login] = UmPanelKeys::ACT_TAB_SEMI_FINAL;
        $this->setSelectedSubTab($login, UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS);

        $this->prevTab[$login] = UmPanelKeys::ACT_TAB_SEMI_FINAL;
        $this->prevSubTab[$login] = UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS;

        $this->boardIsOpen[$login] = true;
        $this->selectedPlayerPaginationIndex[$login] = 0;

        $this->boardMiniIsOpen[$login] = true;

        // if player was disconnected, but now connected again, unset the previous state
        if (isset($this->disconnectedLoginsToUnsetAfterRound[$login])) {
            unset($this->disconnectedLoginsToUnsetAfterRound[$login]);
        }

    }

    public function playerDisconnect($login) {
        if (!isset($this->disconnectedLoginsToUnsetAfterRound[$login])) {
            $this->disconnectedLoginsToUnsetAfterRound[$login] = $login;
        }
    }

    private function unsetDisconnectedPlayers() {
        foreach ($this->disconnectedLoginsToUnsetAfterRound as $login) {
            unset($this->selectedPlayerCollection[$login]);
            unset($this->selectedPlayerIndex[$login]);
            unset($this->selectedPlayer[$login]);
            unset($this->selectedTab[$login]);
            unset($this->selectedSubTab[$login]);
            unset($this->boardIsOpen[$login]);
            unset($this->selectedPlayerPaginationIndex[$login]);
            unset($this->disconnectedLoginsToUnsetAfterRound[$login]);
        }
    }

    private function onEndRace() {
        $this->unsetDisconnectedPlayers();
    }



    public function setSelectedTab($login, $action, $subTabAction = null) {
        if ($action === UmPanelKeys::ACT_TAB_RULES) {
            $this->setSelectedSubTab($login, $subTabAction ?: UmPanelKeys::ACT_SUBTAB_RULES_QUALIFICATION);
        }
        if ($action === UmPanelKeys::ACT_TAB_QUALIFICATION) {
            $this->prevTab[$login] = $action;
            $this->setSelectedSubTab($login, $subTabAction ?: UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD);
        }
        if ($action === UmPanelKeys::ACT_TAB_SEMI_FINAL) {
            $this->prevTab[$login] = $action;
            $this->setSelectedSubTab($login, $subTabAction ?: UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS);
        }
        $this->selectedTab[$login] = $action;
    }

    public function getSelectedTab($login) {
        return isset($this->selectedTab[$login]) ? $this->selectedTab[$login] : UmPanelKeys::ACT_TAB_QUALIFICATION;
    }

    public function setSelectedSubTab($login, $action) {
        $this->selectedSubTab[$login] = $action;
        // reset players page if user change subtab
        //$this->selectedPlayerPaginationIndex[$login] = 0;
        //$this->selectedPlayerIndex[$login] = 0;
        //$this->selectedPlayer[$login] = null;

        // choose appropriate player collection based on subtab
        if ($action === UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD) {
            $this->prevSubTab[$login] = $action;
            $this->selectedPlayerCollection[$login] = $this->qualificationRankings;
            if (isset($this->selectedPlayerCollection[$login]) && count($this->selectedPlayerCollection[$login]) > 0)
            $this->selectedPlayer[$login] = $this->selectedPlayerCollection[$login][0];
            return;
        }

        // choose appropriate player collection based on subtab
        if ($action === UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS) {
            $this->prevSubTab[$login] = $action;
            $this->selectedPlayerCollection[$login] = $this->semiFinalRankings;
            if (isset($this->selectedPlayerCollection[$login]) && count($this->selectedPlayerCollection[$login]) > 0)
            $this->selectedPlayer[$login] = $this->selectedPlayerCollection[$login][0];
            return;
        }

        // choose appropriate player collection based on subtab
        if ($action === UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_STINTS) {
            $this->prevSubTab[$login] = $action;
            if (isset($this->semiFinalRaces) && count($this->semiFinalRaces) > 0) {
                if (!isset($this->selectedSemiFinalRace[$login])) {
                    $this->selectedSemiFinalRace[$login] = $this->semiFinalRaces[0];
                }
            }
            //$this->selectedPlayerCollection[$login] = $this->semiFinalRankings;
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
        return isset($this->selectedSubTab[$login])
            ? $this->selectedSubTab[$login] : UmPanelKeys::ACT_SUBTAB_QUALIFICATION_LEADERBOARD;
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
        if (!isset($this->selectedPlayerPaginationIndex[$login])) {
            $this->selectedPlayerPaginationIndex[$login] = 0;
            return 0;
        }

        return (int)$this->selectedPlayerPaginationIndex[$login];
    }

    public function setSelectedPlayerIndex($login, $action) {
        $rowPrefix = UmPanelKeys::ACT_PLAYERS_SELECT;
        $rowIndex = (int)substr($action, strlen($rowPrefix));
        $this->selectedPlayerIndex[$login] = $rowIndex;

        if ($this->getSelectedSubTab($login) === UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_STINTS) {
            //$this->selectedSubTab[$login] = UmPanelKeys::ACT_SUBTAB_SEMI_FINAL_PLAYER_DETAILS;
            $this->selectedSemiFinalRace[$login] = $this->semiFinalRaces[$rowIndex];
            return;
        }

        // set selected player for convenience
        $currentIndex = $this->getSelectedPlayerPaginationIndex($login);
        if (isset($this->selectedPlayerCollection[$login])) {
            if (isset($this->selectedPlayerCollection[$login][$currentIndex * PLAYERS_PER_PAGE + $rowIndex])) {
                $newSelectedPlayer = $this->selectedPlayerCollection[$login][$currentIndex * PLAYERS_PER_PAGE + $rowIndex];
                $this->selectedPlayer[$login] = $newSelectedPlayer;
            }
        }



    }

    public function setDonations($donations = array()) {
        foreach ($donations as $login => $donation) {
            if (isset($this->players[$login]['NickNameWithColor'])) {
                $donation['NickNameWithColor'] = $this->players[$login]['NickNameWithColor'];
            }
        }

        $this->prizePool = new PrizePool($donations);
    }
}