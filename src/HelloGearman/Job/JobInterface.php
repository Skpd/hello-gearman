<?php

namespace HelloGearman\Job;

use GearmanJob;

interface JobInterface
{
    /** @return string */
    public function getName();
    public function doJob(GearmanJob $job);
}