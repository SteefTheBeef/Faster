<?php

function toString($var) {
    return !is_string($var) ? ''.$var : $var;
}