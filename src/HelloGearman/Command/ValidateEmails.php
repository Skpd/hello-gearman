<?php

namespace HelloGearman\Command;

use GearmanTask;
use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class ValidateEmails implements CommandInterface
{
    private $source;
    private $destination;
    private $client;

    function __construct(\SplFileObject $source, \SplFileObject $destination, \GearmanClient $client)
    {
        $this->source = $source;
        $this->destination = $destination;
        $this->client = $client;
    }

    /**
     * @param ConnectionInterface $from
     * @param string $workload
     * @param Server $server
     * @return mixed
     */
    public function run(Server $server, $workload, ConnectionInterface $from = null)
    {
        throw new \Exception('not implemented');
        return;
        $validateLimit = intval($workload);
        $counter = 0;
        $total = $invalid = $valid = 0;

        $validEmails = [];

        $this->client->setCompleteCallback(function (GearmanTask $task, ConnectionInterface $connection) use ($from, &$total, &$invalid, &$valid, &$validEmails) {
            if ($connection !== $from) return;

            $total++;

            $result = unserialize($task->data());

            if (current($result)) {
                $valid++;

                if ($this->destination->flock(LOCK_EX)) {
                    $this->destination->fwrite(key($result) . PHP_EOL);
                    $this->destination->flock(LOCK_UN);
                }

                $validEmails[] = key($result);
            } else {
                $invalid++;
            }

            $connection->send(new Response('validate-emails-status', [
                'total' => $total,
                'valid' => $valid,
                'invalid' => $invalid,
                'done' => false
            ]));
        });

        $this->client->setCreatedCallback(function (GearmanTask $task, $connection) use ($from) {
//            if ($connection !== $from) return;

            $response = new Response(
                'job-queued',
                [
                    'created' => microtime(1),
                    'handle'  => $task->jobHandle(),
                ]
            );
            $from->send($response);

            var_dump($task->data());
        });

        while (++$counter <= $validateLimit && !$this->source->eof()) {
            $email = trim($this->source->fgets());

            $this->client->addTaskBackground('validate-email', $email, $email);
        }

        $this->client->runTasks();

        $from->send(new Response('validate-emails-status', [
            'total' => $total,
            'valid' => $valid,
            'invalid' => $invalid,
            'done' => true,
            'validEmails' => $validEmails
        ]));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'validate-emails';
    }
}