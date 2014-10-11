<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$worker = new GearmanWorker();
$worker->addServer();

$client = new GearmanClient();
$client->addServer();

$job = new \HelloGearman\Job\ProcessRequest([
    new \HelloGearman\Command\Ping(),
    new \HelloGearman\Command\Fetch(),
    new \HelloGearman\Command\DoBackground($client),
    new \HelloGearman\Command\JobStatus($client),
]);
$worker->addFunction($job->getName(), array($job, 'doJob'));

while ($worker->work());