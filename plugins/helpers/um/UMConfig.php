<?php

require_once('UMConfigEntry.php');

class UMConfig {
    public $um4;

    public function __construct() {
        $this->um4 = new UMConfigEntry();
    }
}