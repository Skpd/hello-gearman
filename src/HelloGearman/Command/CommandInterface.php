<?php

namespace HelloGearman\Command;

use HelloGearman\Request\Request;
use HelloGearman\Response\Response;

interface CommandInterface
{
    /**
     * @param Request $request
     * @return Response
     */
    public function run(Request $request);

    /**
     * @return string
     */
    public function __toString();
}