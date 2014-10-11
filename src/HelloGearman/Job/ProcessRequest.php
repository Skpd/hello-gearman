<?php

namespace HelloGearman\Job;

use GearmanJob;
use HelloGearman\Command\CommandInterface;
use HelloGearman\Request\Request;

class ProcessRequest implements JobInterface
{
    const MESSAGE_ID = 1046;
    const QUEUE_ID   = 1046;

    /** @var CommandInterface[] */
    private $commands;

    function __construct(array $commands)
    {
        foreach ($commands as $command) {
            $this->commands[(string) $command] = $command;
        }
    }

    /** @return string */
    public function getName()
    {
        return 'process-request';
    }

    public function doJob(GearmanJob $job)
    {
        /** @var Request $request */
        $request = unserialize($job->workload());

        if (isset($this->commands[$request->getCommand()])) {
            $response = $this->commands[$request->getCommand()]->run($request);

            msg_send(msg_get_queue(self::QUEUE_ID), self::MESSAGE_ID, $response);
        } else {
            throw new \Exception("Unknown command {$request->getCommand()}");
        }
    }

}