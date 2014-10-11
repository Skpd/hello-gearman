<?php

namespace HelloGearman\Response;

class Response
{
    private $command;
    private $workload;
    private $clientId;
    private $requestId;

    public function __construct($command = null, $workload = null, $clientId = null, $requestId = null)
    {
        $this->command = $command;
        $this->workload = $workload;
        $this->clientId = $clientId;
        $this->requestId = $requestId;
    }

    public function __toString()
    {
        return json_encode(['command' => $this->command, 'workload' => $this->workload, 'requestId' => $this->requestId]);
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

    /**
     * @param null $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return null
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param null $requestId
     */
    public function setRequestId($requestId)
    {
        $this->requestId = $requestId;
    }

    /**
     * @return null
     */
    public function getRequestId()
    {
        return $this->requestId;
    }
}