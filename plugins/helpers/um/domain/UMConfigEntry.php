<?php

class UMConfigEntry {
    public $pointsDistribution = array();
    public $maps = array();

    public function __construct($pointsDistribution, $maps = array()) {
        $this->pointsDistribution = $pointsDistribution;
        $this->maps = $maps;
    }
}