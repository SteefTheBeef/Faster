<?php

class UMConfigEntry {
    public $pointsDistribution = array();

    public function __construct($pointsDistribution) {
        $this->pointsDistribution = $pointsDistribution;
    }
}