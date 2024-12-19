<?php

declare(strict_types=1);

namespace Nahid\QArray;

use Nahid\QArray\Parsers\AstParser;

class Options
{
    public function __construct(
        public readonly ?string $func = null,
        public readonly string $traveler = '.',

    )
    {}
}
