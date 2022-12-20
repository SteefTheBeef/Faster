<?php

function getChallengeID($challengeInfo) {
    return isset($challengeInfo['UId']) ? $challengeInfo['UId'] : 'UID';
}