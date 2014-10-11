<?php

require dirname(__DIR__) . '/vendor/autoload.php';

$worker = new GearmanWorker();
$worker->addServer();

$job = new \HelloGearman\Job\SleepStatus();
$worker->addFunction($job->getName(), array($job, 'doJob'));

$job = new \HelloGearman\Job\ValidateEmail([
    'tcp://gmail-smtp-in.l.google.com:25',
    'tcp://alt1.gmail-smtp-in.l.google.com:25',
    'tcp://alt2.gmail-smtp-in.l.google.com:25',
    'tcp://alt3.gmail-smtp-in.l.google.com:25',
    'tcp://alt4.gmail-smtp-in.l.google.com:25',
]);
$worker->addFunction($job->getName(), array($job, 'doJob'));

while ($worker->work());