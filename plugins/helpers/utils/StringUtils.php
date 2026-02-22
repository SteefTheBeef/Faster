<?php

class StringUtils {
    public static function safeString($str) {
        $str = (string)$str;
        // Prevent attribute/newline weirdness
        $str = str_replace(array("\r", "\n", "\t"), ' ', $str);
        // Escape for XML attribute context (handles both ' and ")
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
    public static function toString($var) {
        return !is_string($var) ? ''.$var : $var;
    }

}
