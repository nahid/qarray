<?php


it('may create Clause class object', function () {
    $clause = new \Nahid\QArray\Clause();

    expect($clause)->toBeInstanceOf(\Nahid\QArray\Clause::class);
});

it('fresh(): works', function () {
    $clause = new \Nahid\QArray\Clause();
    $clause->setTraveler('->');

    expect($clause->fresh(['_traveler' => '.']))
        ->toBeInstanceOf(\Nahid\QArray\Clause::class)
        ->and($clause->getTraveler())->toBe('.');
});

it('collect(): works', function () {
    $clause = new \Nahid\QArray\Clause();
    $instance = $clause->collect(['foo' => 'bar']);

    expect($instance)->toBe($clause);
});

