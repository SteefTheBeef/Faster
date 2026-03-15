<?php

class StringUtils {
    static function mlStripCodes($s) {
        // Remove $xxx color codes, $o/$i/$n style toggles, etc.
        // This is a simple heuristic; adjust if you use more codes.
        return preg_replace('/\$(?:[0-9a-fA-F]{1,3}|[a-zA-Z])/', '', $s);
    }
    static function mlStripBold($s) {
        // Remove $xxx color codes, $o/$i/$n style toggles, etc.
        // This is a simple heuristic; adjust if you use more codes.
        return str_replace('$o', '', $s);
    }
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
