<?php

namespace HelloGearman\Command;

use GearmanClient;
use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

class RunTasks implements CommandInterface
{
    /** @var GearmanClient */
    private $client;

    public function __construct(GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param Request $request
     * @return Response
     */
    public function run(Request $request)
    {
        foreach ($request->getWorkload() as $task) {
            $this->client->addTask($task['name'], $task['workload'], $request);
        }

        $this->client->runTasks();

        $response = new Response(
            'task-done',
            null,
            $request->getClientId(),
            $request->getId()
        );

        return $response;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'run-tasks';
    }
}