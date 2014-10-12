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
        $file = 'view/hello-gearman/' . $request->getWorkload();

        if (!file_exists($file)) {
            $file = 'view/generic/not-found.html';
        }

        return new Response(
            'fetch',
            file_get_contents($file),
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