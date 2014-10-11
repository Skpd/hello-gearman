<?php

namespace HelloGearman\Command;

use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

class Ping implements CommandInterface
{
    /**
     * @param Request $request
     * @return Response|void
     */
    public function run(Request $request)
    {
        echo date(DATE_ATOM) . ": Received ping from " . $request->getClientId() . ': ' . (abs(microtime(1) - $request->getWorkload()) * 1000) . ' ms' . PHP_EOL;

        return new Response('pong', microtime(1), $request->getClientId(), $request->getId());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'ping';
    }
}