<?php
class CloseIcon {
    static function render($x, $y, $actionId) {
        console($actionId);
        return XmlTag::quadIcon64($x, $y, 1.5, 'Circle', (int)$actionId, array('z' => 0.45))
        . XmlTag::quadIcon64($x, $y, 1.5, 'Close', (int)$actionId, array('z' => 0.46));
    }
}