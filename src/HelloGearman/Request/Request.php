<?php

namespace HelloGearman\Request;

class Request
{
    /** @var string */
    private $rawBody = '';
    private $command;
    private $workload;
    private $id;
    private $clientId;

    public function __construct($rawBody)
    {
        $this->setRawBody($rawBody);
        $this->initialize();
    }

    /**
     * @throws InvalidArgumentException
     */
    public function initialize()
    {
        $json = json_decode($this->rawBody, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException(sprintf(
                "Failed to decode '%s': %s (%u)",
                $this->rawBody,
                json_last_error(),
                json_last_error_msg()
            ), InvalidArgumentException::DECODING_FAILED);
        }

        if (!isset($json['command'])) {
            throw new InvalidArgumentException("Command field not found.", InvalidArgumentException::MISSING_COMMAND);
        }

        $this->command = $json['command'];

        if (!isset($json['workload'])) {
            throw new InvalidArgumentException("Workload field not found.", InvalidArgumentException::MISSING_WORKLOAD);
        }

        $this->workload = $json['workload'];

        if (!isset($json['requestId'])) {
            throw new InvalidArgumentException("Request ID field not found.", InvalidArgumentException::MISSING_WORKLOAD);
        }

        $this->id = $json['requestId'];
    }

    /**
     * @param string $rawBody
     */
    public function setRawBody($rawBody)
    {
        $this->rawBody = $rawBody;
    }

    /**
     * @return string
     */
    public function getRawBody()
    {
        return $this->rawBody;
    }

    /**
     * @param string $command
     */
    public function setCommand($command)
    {
        $this->command = $command;
    }

    /**
     * @return string
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
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }
}