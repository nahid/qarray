<?php

namespace Nahid\QArray\Exceptions;

class InvalidQueryFunctionException extends \Exception
{
    public function __construct($function_name = "", $code = 0, \Throwable $previous = null)
    {
        parent::__construct("Invalid query function {$function_name} called", $code, $previous);
    }
}
