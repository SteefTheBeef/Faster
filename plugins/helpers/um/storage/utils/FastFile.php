<?php

/**
 * Small file/dir helper for FAST plugins (PHP 5.6).
 * Centralizes mkdir/touch/read/write/atomic write.
 */
class FastFile {
    public static function ensureDir($dir) {
        if (!is_string($dir) || $dir === '') return false;
        if (is_dir($dir)) return true;

        // recursive mkdir, ignore warnings (existing code style)
        @mkdir($dir, 0777, true);

        return is_dir($dir);
    }

    public static function ensureFile($filePath) {
        if (!is_string($filePath) || $filePath === '') return false;

        $dir = dirname($filePath);
        if (!self::ensureDir($dir)) return false;

        if (file_exists($filePath)) return true;

        $h = @fopen($filePath, 'ab'); // create if missing
        if (!$h) return false;
        fclose($h);

        return file_exists($filePath);
    }

    public static function readLines($filePath) {
        if (!is_string($filePath) || $filePath === '') return array();
        if (!file_exists($filePath)) return array();

        $lines = @file($filePath, FILE_IGNORE_NEW_LINES);
        if ($lines === false || !is_array($lines)) return array();

        return $lines;
    }

    /**
     * Atomic-ish write:
     * - write to temp file
     * - optional flock during write
     * - rename temp to final
     */
    public static function atomicWrite($filePath, $content, $useLock) {
        if (!is_string($filePath) || $filePath === '') return false;

        $dir = dirname($filePath);
        if (!self::ensureDir($dir)) return false;

        $tmp = $filePath . '.tmp';
        $h = @fopen($tmp, 'wb');
        if (!$h) return false;

        $ok = true;

        if ($useLock) {
            if (@flock($h, LOCK_EX)) {
                $ok = (fwrite($h, $content) !== false);
                @fflush($h);
                @flock($h, LOCK_UN);
            } else {
                $ok = (fwrite($h, $content) !== false);
            }
        } else {
            $ok = (fwrite($h, $content) !== false);
        }

        fclose($h);

        if (!$ok) {
            @unlink($tmp);
            return false;
        }

        // On Windows, rename() fails if destination exists. Remove first to be safe.
        if (file_exists($filePath)) {
            @unlink($filePath);
        }

        $renamed = @rename($tmp, $filePath);
        if (!$renamed) {
            @unlink($tmp);
            return false;
        }

        return true;
    }
}