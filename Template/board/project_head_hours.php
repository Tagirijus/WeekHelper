<?php

$times = $tagiTimes($project['id']);
$captions = $this->hoursViewHelper->getLevelCaptions();
$projectSite = true;

require(__DIR__ . '/../hours_header.php');
