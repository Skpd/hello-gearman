<?php

namespace HelloGearman\Command;

use GearmanClient;
use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

class DoBackground implements CommandInterface
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
        switch (isset($request->getWorkload()['priority']) ?: 0) {
            case 1:
                $method = 'doHighBackground';
                break;
            case -1:
                $method = 'doLowBackground';
                break;
            default:
                $method = 'doBackground';
                break;
        }

        $handle = $this->client->$method(
            $request->getWorkload()['name'],
            serialize(isset($request->getWorkload()['data']) ? $request->getWorkload()['data'] : null)
        );

        $response = new Response(
            'job-queued',
            [
                'created'   => microtime(1),
                'handle'    => $handle
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
        return 'do-background';
    }
}