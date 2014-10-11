<?php

namespace HelloGearman\Command;

use GearmanClient;
use HelloGearman\Request\Request;
use HelloGearman\Response\Response;
use HelloGearman\Server;
use Ratchet\ConnectionInterface;

class JobStatus implements CommandInterface
{
    /** @var GearmanClient */
    private $client;

    public function __construct(GearmanClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param Request $request
     * @return Response|void
     */
    public function run(Request $request)
    {
        $status = $this->client->jobStatus($request->getWorkload());

        $response = new Response(
            'job-status',
            [
                'handle'      => $request->getWorkload(),
                'known'       => $status[0],
                'running'     => $status[1],
                'numerator'   => $status[2],
                'denominator' => $status[3],
            ],
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
        return 'job-status';
    }
}