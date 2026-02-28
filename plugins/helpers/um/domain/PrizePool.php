<?php

class PrizePool {
    public $donations = array();
    public $config;

    public function __construct($donations = array()) {
        $this->donations = $donations;
        $this->loadConfig();
    }

    private function loadConfig() {
        $path = 'fastlog/um/config/prizePool.json';
        // __DIR__ .

        $json = file_get_contents($path);
        if ($json === false) {
            throw new RuntimeException('Cannot read prizePool.json');
        }

        $this->config = json_decode($json, true);
        if (!is_array($this->config)) {
            throw new RuntimeException('Invalid JSON: ' . json_last_error_msg());
        }

        $cfg = &$this->config;
        $semiFinalStintsCount = 2 * 7;
        $grandFinalStintsCount = 3 * 7;

        $cfg['DonationsTotalAmount'] = array_sum(array_column($this->donations, 'Amount'));
        $cfg['SemiFinalStintsTotalAmount'] = $cfg['Stints']['SemiFinal'] * $semiFinalStintsCount;
        $cfg['GrandFinalStintsTotalAmount'] = $cfg['Stints']['GrandFinal'] * $grandFinalStintsCount;
        $cfg['QualificationTotalAmount'] = $cfg['Qualification']['BestRacePerEnvironment'] * 7 + $cfg['Qualification']['BestLapPerEnvironment'] * 7;
        $cfg['GrandFinalOverallRankTotalAmount'] = $cfg['DonationsTotalAmount'] - $cfg['SemiFinalStintsTotalAmount']
            - $cfg['GrandFinalStintsTotalAmount'] -  $cfg['QualificationTotalAmount'] - $cfg['BestMap'];

        foreach($cfg['GfRankDistribution'] as &$gfRank) {
            $gfRank['Amount'] = round($gfRank['Percent'] * $cfg['GrandFinalOverallRankTotalAmount']);
        }

        console(print_r($this->config, true));

    }
}