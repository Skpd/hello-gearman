<?php

use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

require dirname(__DIR__) . '/vendor/autoload.php';
$config = include dirname(__DIR__) . '/config/gearman.local.php';

$worker = new GearmanWorker();
$worker->addServer();

$responseClient = new GearmanClient();
$responseClient->addServer();

$client = new GearmanClient();
$client->addServers(implode(',', $config['servers']));

$client->setStatusCallback(function (GearmanTask $task, Request $request) use ($responseClient) {
    $response = new Response(
        'task-status',
        [
            'data'        => $task->data(),
            'unique'      => $task->unique(),
            'known'       => $task->isKnown(),
            'running'     => $task->isRunning(),
            'numerator'   => $task->taskNumerator(),
            'denominator' => $task->taskDenominator(),
        ],
        $request->getClientId(),
        $request->getId()
    );

    $responseClient->doBackground('process-response', serialize($response));
});

$client->setCreatedCallback(function (GearmanTask $task, Request $request) use ($responseClient) {
    $response = new Response(
        'task-created',
        [
            'data'        => $task->data(),
            'unique'      => $task->unique(),
            'known'       => $task->isKnown(),
            'running'     => $task->isRunning(),
            'numerator'   => $task->taskNumerator(),
            'denominator' => $task->taskDenominator(),
        ],
        $request->getClientId(),
        $request->getId()
    );

    $responseClient->doBackground('process-response', serialize($response));
});

$client->setCompleteCallback(function (GearmanTask $task, Request $request) use ($responseClient) {
    $response = new Response(
        'task-complete',
        [
            'data'        => $task->data(),
            'unique'      => $task->unique(),
            'known'       => $task->isKnown(),
            'running'     => $task->isRunning(),
            'numerator'   => $task->taskNumerator(),
            'denominator' => $task->taskDenominator(),
        ],
        $request->getClientId(),
        $request->getId()
    );

    $responseClient->doBackground('process-response', serialize($response));
});

$client->setDataCallback(function (GearmanTask $task, Request $request) use ($responseClient) {
    $response = new Response(
        'task-data',
        [
            'data'        => $task->data(),
            'unique'      => $task->unique(),
            'known'       => $task->isKnown(),
            'running'     => $task->isRunning(),
            'numerator'   => $task->taskNumerator(),
            'denominator' => $task->taskDenominator(),
        ],
        $request->getClientId(),
        $request->getId()
    );

    $responseClient->doBackground('process-response', serialize($response));
});


$job = new \HelloGearman\Job\ProcessRequest($responseClient, [
    new \HelloGearman\Command\Ping(),
    new \HelloGearman\Command\Fetch(),
    new \HelloGearman\Command\DoBackground($client),
    new \HelloGearman\Command\JobStatus($client),
    new \HelloGearman\Command\RunTasks($client)
]);
$worker->addFunction($job->getName(), array($job, 'doJob'));

while ($worker->work());