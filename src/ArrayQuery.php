<?php

namespace Nahid\QArray;

use Nahid\QArray\QueryEngine;

class ArrayQuery extends QueryEngine
{
    /**
     * @var null|QueryEngine
     */
    public static $instance = null;

    public function __construct($data = [])
    {
        if (is_array($data)) {
            $this->collect($data);
        } else {
            parent::__construct($data);
        }
    }

    public function readPath($file)
    {
        return '{}';
    }

    public function parseData($data)
    {

        return $this->collect([]);
    }
}