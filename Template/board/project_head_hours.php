<?php

$times = $this->hoursViewHelper->getTimesByProjectId($project['id']);
$captions = $this->hoursViewHelper->getLevelCaptions();
$projectSite = true;

require(__DIR__ . '/../hours_header.php');
