<?php

namespace Nahid\QArray\Exceptions;

class KeyNotPresentException extends \Exception
{
    public function __construct($message = "Key or property not present exception", $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
