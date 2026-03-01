<?php
class Arrays {
    static function find(array $items, $key, $value, $returnWithIndex = false) {
        $index = 0;
        foreach ($items as $item) {
            // works for arrays
            if (is_array($item) && array_key_exists($key, $item) && $item[$key] == $value) {
                return $returnWithIndex ? array('item' => $item, 'index' => $index) : $item;
            }
            // works for objects
            if (is_object($item) && isset($item->$key) && $item->$key == $value) {
                return $returnWithIndex ? array('item' => $item, 'index' => $index) : $item;
            }
            $index++;
        }
        return null; // not found
    }
}