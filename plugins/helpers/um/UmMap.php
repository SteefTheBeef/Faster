<?php
class UmMap {
    public $name;
    public $nameWithColor;
    public $id;
    public $author;

    public function __construct($id, $name = "", $nameWithColor = "", $author = "") {
        $this->id = $id;
        $this->name = $name;
        $this->nameWithColor = $nameWithColor;
        $this->author = $author;
    }
}