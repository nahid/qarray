<?php

namespace Nahid\QArray;

use Nahid\QArray\QueryEngine;

class JsonBuilderEngine extends QueryEngine
{
    public function readPath($file)
    {
        $raw_data = file_get_contents($file);
        $json = json_decode($raw_data, true);

        return $json;
    }

    public function parseData($data)
    {
        $json = json_decode($data, true);

        return $this->collect($json);
    }
}