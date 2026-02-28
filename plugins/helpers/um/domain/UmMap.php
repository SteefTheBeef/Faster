<?php

class UmMap {
    public $name;
    public $nameWithColor;
    public $id;
    public $author;
    public $environment;

    public function __construct($id, $name = "", $nameWithColor = "", $author = "", $environment = "") {
        $this->id = $id;
        $this->name = $name;
        $this->nameWithColor = $nameWithColor;
        $this->author = $author;
        $this->environment = $environment;
    }
}