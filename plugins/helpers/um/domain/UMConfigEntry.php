<?php

class UMConfigEntry {
    public $pointsDistribution = array();
    public $maps = array();
    public $lapsCount;
    public $stintsPerMap;

    public $filePath;

    public function __construct($pointsDistribution, $maps = array(), $donations = array(), $lapsCount = 0, $stintsPerMap = 1, $filePath = '') {
        $this->pointsDistribution = $pointsDistribution;
        $this->maps = $maps;
        $this->lapsCount = $lapsCount;
        $this->stintsPerMap = $stintsPerMap;
        $this->filePath = $filePath;
    }
}