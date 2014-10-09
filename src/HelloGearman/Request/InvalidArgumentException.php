<?php

namespace HelloGearman\Request;

class InvalidArgumentException extends \InvalidArgumentException
{
    const DECODING_FAILED  = 1;
    const MISSING_COMMAND  = 2;
    const MISSING_WORKLOAD = 4;
}