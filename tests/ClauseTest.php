<?php


it('may create Clause class object', function () {
    $clause = new \Nahid\QArray\Clause();

    expect($clause)->toBeInstanceOf(\Nahid\QArray\Clause::class);
});


it('collect(): works', function () {
    $clause = new \Nahid\QArray\Clause();
    $instance = $clause->collect(['foo' => 'bar']);

    expect($instance)->toBe($clause);
});

