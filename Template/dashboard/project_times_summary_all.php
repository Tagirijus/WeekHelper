<?php

$times = $tagiTimes($user['id']);
$captions = $this->hoursViewHelper->getLevelCaptions();
$projectSite = false;

require(__DIR__ . '/../hours_header.php');
