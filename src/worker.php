<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$worker = new GearmanWorker();
$worker->addServer();

$job = new \HelloGearman\Job\SleepStatus();
$worker->addFunction($job->getName(), array($job, 'doJob'));

while ($worker->work());