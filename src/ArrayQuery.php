<?php

declare(strict_types=1);

namespace Nahid\QArray;

use Nahid\QArray\QueryEngine;

class ArrayQuery extends QueryEngine
{
    /**
     * @var null|QueryEngine
     */
    protected static ?QueryEngine $instance = null;

    public function __construct(array $data = [])
    {
        $this->collect($data);
    }

    public static function getInstance(): QueryEngine
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    public function readPath(string $path): string
    {
        return '{}';
    }

    public function parseData(mixed $data): mixed
    {
        return $this->collect([]);
    }
}