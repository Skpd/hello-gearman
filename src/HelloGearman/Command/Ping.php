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