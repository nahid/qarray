<?php

declare(strict_types=1);

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\InvalidJsonException;

class Query extends QueryEngine
{
    /**
     * @var null|Query
     */
    protected static ?Query $instance = null;

    /**
     * @param array<string, mixed>|null $data
     * @return static
     */
    public static function new(?array $data = null): static
    {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }

        if (!is_null($data)) {
            static::$instance->collect($data);
        }

        return static::$instance;
    }

    public function readPath(string $path): array
    {
        return [];
    }

    public function parseData(mixed $data): array
    {
        return [];
    }

    public function useUrl(string $url): QueryEngine
    {
        $contents = file_get_contents($url);

        return $this->useJson($contents);
    }

    public function useJson(string $json): QueryEngine
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidJsonException();
        }

        return $this->collect($data);
    }

    public function useArray(array $data): QueryEngine
    {
        return $this->collect($data);
    }

}
