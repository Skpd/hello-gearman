<?php

namespace HelloGearman\Response;

class Response
{
    private $command;
    private $workload;

    public function __construct($command = null, $workload = null)
    {
        $this->command = $command;
        $this->workload = $workload;
    }

    public function __toString()
    {
        return json_encode(['command' => $this->command, 'workload' => $this->workload]);
    }

    /**
     * @param mixed $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return mixed
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @param mixed $workload
     */
    public function setWorkload($workload)
    {
        $this->workload = $workload;
    }

    /**
     * @return mixed
     */
    public function getWorkload()
    {
        return $this->workload;
    }
}