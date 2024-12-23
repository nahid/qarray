<?php

use Nahid\QArray\QueryEngine;

require_once 'mock_data.php';
function get_query_engine_instance_with_data(): ?QueryEngine
{
    $query = new class extends QueryEngine {
        public function readPath(string $path): array { return []; }
        public function parseData(string|array $data): array { return []; }
    };

    $file = __DIR__ . '/mock-data.json';
    $contents = file_get_contents($file);

    $data = json_decode($contents, true);

    return $query->collect($data);
}

/**
 * @template TKey
 * @template TValue
 * @return QueryEngine<TKey, TValue>
 */
function get_query_engine_instance(): QueryEngine
{
    return new class extends QueryEngine {
        public function readPath(string $path): array { return []; }
        public function parseData(string|array $data): array { return []; }
    };

}

it('can create a deep copy of the QueryEngine instance', function () {
    $queryEngine = get_query_engine_instance_with_data();

    $queryEngine->__set('key', 'value');
    $copy = $queryEngine->copy();

    expect($copy)->not->toBe($queryEngine)
        ->and($copy->__get('key'))->toBe('value')
        ->and($copy)->toEqual($queryEngine);
});

it('can make string representation of the QueryEngine instance', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->__set('key', 'value');

    $string = (string) $queryEngine;

    expect($string)->toBeString()
        ->and($string)->toContain('key');
});

it('can __set and __get the data of the QueryEngine instance', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->__set('key', 'value');

    expect($queryEngine->__get('key'))->toBe('value');
});

it('can __invoke the QueryEngine instance', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->__set('key', 'value');

    expect($queryEngine())->toBeArray()
        ->and($queryEngine())->toMatchArray(['key' => 'value']);
});

it('at() can get the value of the given key', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect(['foo' => [
        'bar' => 'baz'
    ]]);

    expect($queryEngine->at('foo')->toArray())->toMatchArray(['bar' => 'baz']);
});

it('at() can get the value from the nested node', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect(['foo' => [
        'bar' => [
            'baz' => 'qux'
        ]
    ]]);

    expect($queryEngine->at('foo.bar', 'default')->toArray())->toMatchArray(['baz' => 'qux']);
});

it('can get() the value of the query result', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect([
        ['id' => 1, 'name' => 'foo'],
        ['id' => 2, 'name' => 'bar'],
        ['id' => 3, 'name' => 'baz'],
    ]);

    expect($queryEngine->get()->toArray())->toMatchArray([
        ['id' => 1, 'name' => 'foo'],
        ['id' => 2, 'name' => 'bar'],
        ['id' => 3, 'name' => 'baz'],
    ])
        ->and($queryEngine->get())->toBeInstanceOf(QueryEngine::class)
        ->and($queryEngine->get())->toHaveProperty('_data');
});

it('can get() the value of the query result with the given properties', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect([
        ['id' => 1, 'name' => 'foo'],
        ['id' => 2, 'name' => 'bar'],
        ['id' => 3, 'name' => 'baz'],
    ]);

    expect($queryEngine->get(['id'])->toArray())->toMatchArray([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ]);
});

it('can raw() get the raw data from the query', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect([
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
    ]);

    expect($queryEngine->raw())->toBeArray("It should return the raw data")
        ->and($queryEngine->raw())->toMatchArray([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ], "It should be the same as the original data");
});


it('can raw() get the raw data from the query with the given column', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect([
        ['id' => 1, 'name' => 'foo'],
        ['id' => 2, 'name' => 'bar'],
        ['id' => 3, 'name' => 'baz'],
    ]);

    expect($queryEngine->raw(['id']))->toBeArray("It should return the raw data")
        ->and($queryEngine->raw())->toMatchArray([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ], "It should be the same as the original data");
});

it('can exists() check the data existence', function () {
    $queryEngine = get_query_engine_instance();
    $queryEngine->collect([
        ['id' => 1, 'name' => 'foo'],
        ['id' => 2, 'name' => 'bar'],
        ['id' => 3, 'name' => 'baz'],
    ]);

    expect($queryEngine->where('id', 2)->exists())->toBeTrue("It should return true")
        ->and($queryEngine->where('id', 4)->exists())->toBeFalse("It should return false");

});

describe("reset()", function() {

    it('can reset current instance with original data', function () {
        $queryEngine = get_query_engine_instance();
        $queryEngine->collect([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ]);

        $newQuery = $queryEngine->where('id', 2)->get();

        $queryEngine->reset();

        expect($newQuery->raw())->toMatchArray([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ]);
    });

    it('can reset current instance with new data', function () {
        $queryEngine = get_query_engine_instance();
        $queryEngine->collect([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ]);

        $newQuery = $queryEngine->where('id', 2)->get();

        $queryEngine->reset([
            ['id' => 4, 'name' => 'qux'],
            ['id' => 5, 'name' => 'quux'],
        ]);

        expect($newQuery->raw())->toMatchArray([
            ['id' => 4, 'name' => 'qux'],
            ['id' => 5, 'name' => 'quux'],
        ]);
    });

    it('can reset current instance with new instance', function () {
        $queryEngine = get_query_engine_instance();
        $queryEngine->collect([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ]);

        $newQuery = $queryEngine->where('id', 2)->get();

        $newInstance = $queryEngine->reset(instance: true);

        expect($newQuery)->not->toBe($newInstance)
            ->and($newInstance->raw())->toMatchArray([
                ['id' => 1, 'name' => 'foo'],
                ['id' => 2, 'name' => 'bar'],
                ['id' => 3, 'name' => 'baz'],
            ]);
    });

    it('can reset current instance with modifying props', function () {
        $queryEngine = get_query_engine_instance();
        $queryEngine->collect([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
        ]);

        $newQuery = $queryEngine->where('id', 2)->get();

        $queryEngine->reset(props: ['_take' => 2]);

        expect($queryEngine->get()->raw())->toHaveCount(2);
    });

    it('can reset current instance with modifying props and new instance', function () {
        $queryEngine = get_query_engine_instance();
        $queryEngine->collect([
            ['id' => 1, 'name' => 'foo'],
            ['id' => 2, 'name' => 'bar'],
            ['id' => 3, 'name' => 'baz'],
            ['id' => 4, 'name' => 'baz'],
            ['id' => 5, 'name' => 'baz'],
        ]);

        $newQuery = $queryEngine->where('id', 2)->get();

        $newInstance = $queryEngine->reset(props: ['_take' => 4], instance: true);

        expect($newInstance->get()->raw())->toHaveCount(4);
    });


});

it('can groupBy the data with specific property', function () {
  $queryEngine = get_query_engine_instance();
  $mockData = get_mock_data();

  $queryEngine->collect($mockData);

  $result = $queryEngine->groupBy('deleted_at');
  expect($result->raw())->toHaveKey('2023-01-01 00:00:00')
      ->and($result->raw()['2023-01-01 00:00:00'])->toHaveCount(2);

});

it('can countGroupBy the data with specific property', function () {
  $queryEngine = get_query_engine_instance();
  $mockData = get_mock_data();

  $queryEngine->collect($mockData);

  $result = $queryEngine->countGroupBy('deleted_at');
  expect($result->raw())->toHaveKey('2023-01-01 00:00:00')
      ->and($result->raw()['2023-01-01 00:00:00'])->toBe(2);

});

it('can distinct the data with specific property', function () {
  $queryEngine = get_query_engine_instance();
  $mockData = get_mock_data();

  $queryEngine->collect($mockData);

  $result = $queryEngine->distinct('deleted_at');
  expect($result->raw())->toHaveCount(5)
      ->and($result->raw()[0])->toHaveKey('deleted_at', '2023-01-01 00:00:00');

});

describe('count()', function () {
    it('can count the data', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->count();
        expect($result)->toBe(20);
    });

    it('can count the data with query results', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->where('is_active', false)->count();
        expect($result)->toBe(5);
    });
});

describe('sum()', function () {
    it('can sum the collection data', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->sum('age');
        expect($result)->toBe(560);
    });

    it('can sum the collection data of querying resutls', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->where('age', '>', 30)->sum('age');
        expect($result)->toBe(238);
    });

    it('can sum the sets of value', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $result = $queryEngine->sum();
        expect($result)->toBe(28);
    });


    it('can not sum non collection value with given key', function () {
        $queryEngine = get_query_engine_instance();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $queryEngine->sum('age');
    })->throws(\Nahid\QArray\Exceptions\InvalidArgumentException::class);


    it('can not sum collection of value without any given key', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $queryEngine->sum();
    })->throws(\Nahid\QArray\Exceptions\KeyNotPresentException::class);
});

describe('max()', function () {
    it('can max() the collection data', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->max('age');
        expect($result)->toBe(37);
    });

    it('can max() the collection data of querying results', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->where('age', '<', 30)->max('age');
        expect($result)->toBe(29);
    });

    it('can max() the sets of value', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect([7, 3, 6, 2, 5, 9, 4]);

        $result = $queryEngine->max();
        expect($result)->toBe(9);
    });

    it('can not max() non collection value with given key', function () {
        $queryEngine = get_query_engine_instance();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $queryEngine->max('age');
    })->throws(\Nahid\QArray\Exceptions\InvalidArgumentException::class);

    it('can not max() collection of value without any given key', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $queryEngine->max();
    })->throws(\Nahid\QArray\Exceptions\KeyNotPresentException::class);
});

describe('min()', function () {
    it('can min() the collection data', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->min('age');
        expect($result)->toBe(20);
    });

    it('can min() the collection data of querying results', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->where('age', '>', 30)->min('age');
        expect($result)->toBe(31);
    });

    it('can min() the sets of value', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $result = $queryEngine->min();
        expect($result)->toBe(1);
    });

    it('can not min() non collection value with given key', function () {
        $queryEngine = get_query_engine_instance();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $queryEngine->min('age');
    })->throws(\Nahid\QArray\Exceptions\InvalidArgumentException::class);

    it('can not min() collection of value without any given key', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $queryEngine->min();
    })->throws(\Nahid\QArray\Exceptions\KeyNotPresentException::class);

});

describe('avg()', function () {
    it('can avg() the collection data', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->avg('age');
        expect($result)->toBe(28);
    });

    it('can avg() the collection data of querying results', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $result = $queryEngine->where('age', '>', 30)->avg('age');
        expect($result)->toBe(34);
    });

    it('can avg() the sets of value', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $result = $queryEngine->avg();
        expect($result)->toBe(4);
    });

    it('can not avg() non collection value with given key', function () {
        $queryEngine = get_query_engine_instance();

        $queryEngine->collect([7, 3, 6, 2, 5, 1, 4]);

        $queryEngine->avg('age');
    })->throws(\Nahid\QArray\Exceptions\InvalidArgumentException::class);

    it('can not avg() collection of value without any given key', function () {
        $queryEngine = get_query_engine_instance();
        $mockData = get_mock_data();

        $queryEngine->collect($mockData);

        $queryEngine->avg();
    })->throws(\Nahid\QArray\Exceptions\KeyNotPresentException::class);
});





