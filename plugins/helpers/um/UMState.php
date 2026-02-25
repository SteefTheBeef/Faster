<?php
require_once "ScoresMerger.php";
require_once "UmPlayers.php";
require_once __DIR__ . '/../matchlog/BestRaces.php';

class UMState {
    public $shouldUpdateXml = true;
    public $shouldComputeRankings = true;
    public $qualificationBestRacesRanking = array();
    public $qualificationBestLapsRanking = array();

    public $qualificationRankings = array();

    public $qualiConfigBestRaces;
    public $qualiConfigBestLaps;

    public $players = array();

    public function __construct($qualiConfigBestRaces, $qualiConfigBestLaps) {
        $this->qualificationBestRacesRanking = array();
        $this->qualificationBestLapsRanking = array();
        $this->shouldUpdateXml = true;
        $this->shouldComputeRankings = true;
        $this->qualiConfigBestRaces = $qualiConfigBestRaces;
        $this->qualiConfigBestLaps = $qualiConfigBestLaps;
    }

    public function computeRankings() {
        console("COMPUTING NEW RANKINGS!!!!");

        if (!$this->shouldComputeRankings) return;

        $this->players = UmPlayers::loadPlayersNicknamesMap();
        $this->qualificationBestRacesRanking = BestRaces::buildQualificationRankingsAllMaps($this->qualiConfigBestRaces, $this->players);
        $this->qualificationBestLapsRanking = BestRaces::buildQualificationRankingsAllMapsBestLaps($this->qualiConfigBestLaps, $this->players);
        $this->qualificationRankings = ScoresMerger::mergeQualificationScoresByEnv($this->qualificationBestRacesRanking, $this->qualificationBestLapsRanking);
        //console("COMPUTED NEW RANKINGS");
        //console(print_r($this->qualificationRankings, true));
        $this->shouldComputeRankings = false;
    }
}