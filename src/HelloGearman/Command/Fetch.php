<?php

namespace HelloGearman\Command;

use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

class Fetch implements CommandInterface
{
    /**
     * @param Request $request
     * @return Response|void
     */
    public function run(Request $request)
    {
        return new Response(
            'fetch',
            '<pre><code>' . file_get_contents($request->getWorkload()) . '</code></pre>',
            $request->getClientId(),
            $request->getId()
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return 'fetch';
    }
}