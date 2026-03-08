<?php

class UmMap {
    public $name;
    public $nameWithColor;
    public $id;
    public $author;
    public $environment;

    public function __construct($id, $nameWithColor = "", $author = "", $environment = "") {
        $this->id = $id;
        $this->nameWithColor = $nameWithColor;
        $this->author = $author;
        $this->environment = $environment;
    }

    public function toRatingsArray() {
        return array(
            "ChallengeID" => $this->id,
            "Name" => $this->nameWithColor,
            "Environment" => $this->environment,
            "Rank" => 1, // default value
            "TotalRanks" => 0,
            "RanksCount" => 0,
        );
    }
}