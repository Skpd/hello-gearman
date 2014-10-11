<?php

namespace HelloGearman\Job;

use EmailTester\SyncClient;
use GearmanJob;

class ValidateEmail implements JobInterface
{
    private $servers = [];

    function __construct(array $servers)
    {
        $this->servers = $servers;
    }

    /** @return string */
    public function getName()
    {
        return 'validate-email';
    }

    public function doJob(GearmanJob $job)
    {
        $email = $job->workload();
        $job->sendData($email);

        $job->sendStatus(10, 100);

        $client = new SyncClient();
        $client->connect($this->servers[mt_rand(0, count($this->servers) - 1)]);

        $result = $client->checkEmail($email);

        $client->disconnect();
        $job->sendStatus(100, 100);

        $job->sendComplete(serialize([$email => $result]));
    }
}