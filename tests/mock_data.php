<?php


/**
 * @return array<string, mixed>
 */
function get_mock_data(): array
{
    return [
        ['id' => 1, 'name' => 'foo', 'age' => 20, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-01 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'qux']],
        ['id' => 2, 'name' => 'bar', 'age' => 25, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-02 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'quux']],
        ['id' => 3, 'name' => 'baz', 'age' => 30, 'is_active' => false, 'deleted_at' => null, 'created_at' => '2021-01-03 00:00:00', 'meta' => ['foo' => 'quux', 'baz' => 'foo']],
        ['id' => 4, 'name' => 'qux', 'age' => 35, 'is_active' => true, 'deleted_at' => '2023-01-01 00:00:00', 'created_at' => '2021-01-04 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'baz']],
        ['id' => 5, 'name' => 'quux', 'age' => 22, 'is_active' => false, 'deleted_at' => '2023-01-01 00:00:00', 'created_at' => '2021-01-05 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'quux']],
        ['id' => 6, 'name' => 'corge', 'age' => 28, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-06 00:00:00', 'meta' => ['foo' => 'qux', 'baz' => 'bar']],
        ['id' => 7, 'name' => 'grault', 'age' => 26, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-07 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'baz']],
        ['id' => 8, 'name' => 'garply', 'age' => 21, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-08 00:00:00', 'meta' => ['foo' => 'quux', 'baz' => 'foo']],
        ['id' => 9, 'name' => 'waldo', 'age' => 27, 'is_active' => true, 'deleted_at' => '2023-01-15 00:00:00', 'created_at' => '2021-01-09 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'qux']],
        ['id' => 10, 'name' => 'fred', 'age' => 33, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-10 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'quux']],
        ['id' => 11, 'name' => 'plugh', 'age' => 29, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-11 00:00:00', 'meta' => ['foo' => 'qux', 'baz' => 'foo']],
        ['id' => 12, 'name' => 'xyzzy', 'age' => 31, 'is_active' => false, 'deleted_at' => null, 'created_at' => '2021-01-12 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'quux']],
        ['id' => 13, 'name' => 'thud', 'age' => 34, 'is_active' => true, 'deleted_at' => '2023-02-01 00:00:00', 'created_at' => '2021-01-13 00:00:00', 'meta' => ['foo' => 'quux', 'baz' => 'bar']],
        ['id' => 14, 'name' => 'foobar', 'age' => 23, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-14 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'quux']],
        ['id' => 15, 'name' => 'barbaz', 'age' => 37, 'is_active' => false, 'deleted_at' => null, 'created_at' => '2021-01-15 00:00:00', 'meta' => ['foo' => 'qux', 'baz' => 'foo']],
        ['id' => 16, 'name' => 'bazqux', 'age' => 24, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-16 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'quux']],
        ['id' => 17, 'name' => 'quuxquuz', 'age' => 32, 'is_active' => true, 'deleted_at' => '2023-03-01 00:00:00', 'created_at' => '2021-01-17 00:00:00', 'meta' => ['foo' => 'quux', 'baz' => 'baz']],
        ['id' => 18, 'name' => 'corgegrault', 'age' => 25, 'is_active' => true, 'deleted_at' => null, 'created_at' => '2021-01-18 00:00:00', 'meta' => ['foo' => 'bar', 'baz' => 'qux']],
        ['id' => 19, 'name' => 'graultgarply', 'age' => 22, 'is_active' => false, 'deleted_at' => null, 'created_at' => '2021-01-19 00:00:00', 'meta' => ['foo' => 'qux', 'baz' => 'bar']],
        ['id' => 20, 'name' => 'waldofoo', 'age' => 36, 'is_active' => true, 'deleted_at' => '2023-04-01 00:00:00', 'created_at' => '2021-01-20 00:00:00', 'meta' => ['foo' => 'baz', 'baz' => 'foo']],
    ];
}



