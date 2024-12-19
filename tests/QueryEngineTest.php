<?php

use Nahid\QArray\QueryEngine;

function get_query_engine_instance(): QueryEngine
{
    return new class extends QueryEngine {
        public function readPath(string $path): array { return []; }
        public function parseData(string|array $data): array { return []; }
    };
}

it('can create a deep copy of the QueryEngine instance', function () {
    $queryEngine = get_query_engine_instance();

    $queryEngine->__set('key', 'value');
    $copy = $queryEngine->copy();

    expect($copy)->not->toBe($queryEngine)
        ->and($copy->__get('key'))->toBe('value')
        ->and($copy)->toEqual($queryEngine);
});
