<?php
class MLState {
    /**
     * Read per-player ML UI state with a default.
     *
     * @param string $login
     * @param string $key Example: 'um.panel.closed'
     * @param mixed $default
     * @return mixed
     */
    static function mlGet($login, $key, $default = null) {
        global $_players;

        if (!isset($_players[$login]) || !isset($_players[$login]['ML']) || !is_array($_players[$login]['ML'])) {
            return $default;
        }
        if (!array_key_exists($key, $_players[$login]['ML'])) {
            return $default;
        }

        return $_players[$login]['ML'][$key];
    }

    /**
     * Write per-player ML UI state (creates ML array if missing).
     *
     * @param string $login
     * @param string $key
     * @param mixed $value
     * @return void
     */
    static function mlSet($login, $key, $value) {
        global $_players;

        if (!isset($_players[$login])) {
            $_players[$login] = array();
        }
        if (!isset($_players[$login]['ML']) || !is_array($_players[$login]['ML'])) {
            $_players[$login]['ML'] = array();
        }

        $_players[$login]['ML'][$key] = $value;
    }
}