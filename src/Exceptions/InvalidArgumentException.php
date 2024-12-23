<?php

namespace Nahid\QArray\Exceptions;

class InvalidArgumentException extends \Exception
{
    public function __construct($message = "Invalid argument exception", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
