<?php

namespace HelloGearman\Job;

use GearmanJob;

class SleepStatus implements JobInterface
{
    /** @return string */
    public function getName()
    {
        return 'sleep-status';
    }

    public function doJob(GearmanJob $job)
    {
        $start = microtime(1);
        $sleep = mt_rand(1, 5);

        do {
            usleep(mt_rand(1, 20) * 10000);

            $diff = microtime(1) - $start;
            $job->sendStatus($diff / ($sleep / 100), 100);

        } while ($diff < $sleep);

        return microtime(1) - $start;
    }

}