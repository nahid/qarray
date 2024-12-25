<?php

declare(strict_types=1);

namespace Nahid\QArray;

use ArrayAccess;
use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use Nahid\QArray\Exceptions\InvalidArgumentException;
use Nahid\QArray\Exceptions\KeyNotPresentException;
use function DeepCopy\deep_copy;

/**
 * @template TKey of array-key
 * @template TValue
 * @extends Clause<TKey, TValue>
 */
abstract class QueryEngine extends Clause implements ArrayAccess, \Iterator, \Countable
{
    /**
     * return json string when echoing the instance
     *
     * @return string
     * @throws ConditionNotAllowedException
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * @param string $path
     * @return array
     */
    public abstract function readPath(string $path): array;

    /**
     * @param string|array $data
     * @return array
     */
    public abstract function parseData(string|array $data): array;

    /**
     * @param mixed $key
     * @return mixed
     * @throws KeyNotPresentException
     */
    public function __get(mixed $key): mixed
    {
        if (isset($this->_data[$key]) or is_null($this->_data[$key])) {
            return $this->_data[$key];
        }

        throw new KeyNotPresentException();
    }

    /**
     * Property override for current object
     *
     * @param TKey $key
     * @param TValue $val
     */
    public function __set(mixed $key, mixed $val): void
    {
        $this->_data[$key] = $val;
    }

    /**
     * @return array<TKey, TValue>
     */
    public function __invoke(): array
    {
        return $this->toArray();
    }

    /**
     * Implementation of ArrayAccess : check existence of the target offset
     *
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess : Get the target offset
     *
     * @param TKey $offset
     * @return mixed|KeyNotExists
     */
    public function offsetGet(mixed $offset): mixed
    {
        if ($this->offsetExists($offset)) {
            return $this->_data[$offset];
        }

        return new KeyNotExists();
    }

    /**
     * Implementation of ArrayAccess : Set the target offset
     *
     * @param TKey $offset
     * @param TValue $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess : Unset the target offset
     *
     * @param TKey $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        if ($this->offsetExists($offset)) {
           unset($this->_data[$offset]);
        }
    }

    /**
     * Implementation of Iterator : Rewind the Iterator to the first element
     *
     * @return void
     */
    public function rewind(): void
    {
        reset($this->_data);
    }

    /**
     * Implementation of Iterator : Return the current element
     * @return mixed
     */
    public function current(): mixed
    {
        $data = current($this->_data);
        if (!is_array($data)) {
            return $data;
        }

        $instance = new static();

        return $instance->collect($data);
    }

    /**
     * Implementation of Iterator : Return the key of the current element
     *
     * @return string|int|null
     */
    public function key(): string|int|null
    {
        return key($this->_data);
    }

    /**
     * Implementation of Iterator : Move forward to next element
     *
     * @return void
     */
    public function next(): void
    {
        next($this->_data);
    }

    /**
     * Implementation of Iterator : Checks if current position is valid
     *
     * @return bool
     */
    public function valid(): bool
    {
        return key($this->_data) !== null;
    }

    /**
     * Deep copy current instance
     *
     * @param bool $fresh
     * @return static
     */
    public function copy(bool $fresh = false): static
    {
        if ($fresh) {
            return $this->reset(data:[], instance: true);
        }

        return deep_copy($this);
    }

    /**
     * Alias of from() method
     *
     * @param string $node
     * @return static
     */
    public function at(string $node = '.'): static
    {
        return $this->from($node);
    }

    /**
     * getting prepared data
     *
     * @param ?array<string> $columns
     * @return static
     */
    public function get(?array $columns = null): static
    {
        $this->setSelectColumns($columns);

        $this->run();

        return $this->processOutput($this->_data);
    }

    /**
     * getting prepared data
     *
     * @param ?array<string> $columns
     * @return mixed
     */
    public function raw(?array $columns = null): mixed
    {
        $this->setSelectColumns($columns);

        $this->run();

        return $this->getData();
    }

    /**
     * alias of get method
     *
     * @param ?array<string> $columns
     * @return static
     */
    public function fetch(?array $columns = null): static
    {
        return $this->get($columns);
    }

    /**
     * check exists data from the query
     *
     * @return bool
     */
    public function exists(): bool
    {
        $this->run();

        return (!empty($this->_data));
    }

    /**
     * getting group data from specific column
     *
     * @param string $column
     * @return static
     */
    public function groupBy(string $column): static
    {
        $this->run();

        $data = [];
        foreach ($this->_data as $row) {
            $value = $this->arrayGet($row, $column);
            if ($value) {

                $key = match (gettype($value)) {
                    'object' => get_class($value),
                    'boolean' => $value ? 'true' : 'false',
                    'NULL' => 'null',
                    default => $value,
                };

                $data[$key][] = $row;
            }
        }

        $this->_data = $data;
        return $this;
    }

    /**
     * Group by count from array value
     *
     * @param string $column
     * @return static
     */
    public function countGroupBy(string $column): static
    {

        $this->run();

        $data = [];
        foreach ($this->_data as $map) {
            $value = $this->arrayGet($map, $column);
            if (!$value) {
                continue;
            }

            if (isset($data[$value])) {
                $data[$value]  ++;
            } else {
                $data[$value] = 1;
            }
        }

        $this->_data = $data;

        return $this;
    }


    /**
     * getting distinct data from specific column
     *
     * @param string $column
     * @return static
     */
    public function distinct(string $column): static
    {
        $this->run();

        $data = [];
        foreach ($this->_data as $map) {
            $value = $this->arrayGet($map, $column);
            if ($value && !array_key_exists($value, $data)) {
                $data[$value] = $map;
            }
        }

        $this->_data = array_values($data);

        return $this;
    }


    /**
     * count prepared data
     *
     * @return int
     */
    public function count(): int
    {
        $this->run();

        return count($this->_data);
    }

    /**
     * size is an alias of count
     *
     * @return int
     */
    public function size(): int
    {
        return $this->count();
    }

    /**
     * @param array $data
     * @param string|null $key
     * @return array
     * @throws InvalidArgumentException
     * @throws KeyNotPresentException
     */
    protected function validateDataCanProcessWithKey(array $data, ?string $key = null): array
    {
        if (!is_null($key) && !$this->isCollection($data)) {
            throw new InvalidArgumentException();
        }

        if (is_null($key) && $this->isCollection($data)) {
            throw new KeyNotPresentException();
        }

        return $data;
    }

    /**
     * sum prepared data
     * @param string|null $column
     * @return int|float
     */
    public function sum(?string $column = null): int|float
    {
        $this->run();
        $data = $this->validateDataCanProcessWithKey($this->_data, $column);


        $sum = 0;
        if (is_null($column) && !$this->isCollection($data)) {
            $sum = array_sum($data);
        } else {
            foreach ($data as $key => $val) {
                $value = $this->arrayGet($val, $column);
                if (is_scalar($value)) {
                    $sum += $value;
                }

            }
        }

        return $sum;
    }

    /**
     * getting max value from prepared data
     *
     * @param string|null $column
     * @return int|float
     */
    public function max(?string $column = null): int|float
    {
        $this->run();
        $data = $this->validateDataCanProcessWithKey($this->_data, $column);

        if (is_null($column) && !$this->isCollection($data)) {
            return max($data);
        }

        if (!is_null($column) && $this->isCollection($data)) {
            $values = [];
            foreach ($data as $val) {
                $values[] = $this->arrayGet($val, $column);
            }

            $data = $values;
        }

        return max($data);
    }

    /**
     * getting min value from prepared data
     *
     * @param string|null $column
     * @return int|float
     */
    public function min(?string $column = null): int|float
    {
        $this->run();
        $data = $this->validateDataCanProcessWithKey($this->_data, $column);

        if (is_null($column) && !$this->isCollection($data)) {
            return min($data);
        }

        if (!is_null($column) && $this->isCollection($data)) {
            $values = [];
            foreach ($data as $val) {
                $values[] = $this->arrayGet($val, $column);
            }

            $data = $values;
        }

        return min($data);
    }

    /**
     * getting average value from prepared data
     *
     * @param string|null $column
     * @return int|float
     */
    public function avg(?string $column = null): int|float
    {
        $this->run();

        $count = $this->count();
        $total = $this->sum($column);

        return ($total/$count);
    }

    /**
     * getting first element of prepared data
     *
     * @param ?array<string> $columns
     * @return static|null
     */
    public function first(?array $columns = null): ?static
    {
        $this->setSelectColumns($columns);
        $this->run();

        $data = $this->_data;

        if (count($data) > 0) {
            return $this->processOutput(reset($data));
        }

        return null;
    }

    /**
     * getting last element of prepared data
     *
     * @param array $columns
     * @return QueryEngine|null
     */
    public function last(?array $columns = null): ?static
    {
        $this->setSelectColumns($columns);
        $this->run();

        $data = $this->_data;

        if (count($data) > 0) {
            return $this->processOutput(end($data));
        }

        return null;
    }

    /**
     * getting nth number of element of prepared data
     *
     * @param int $index
     * @param ?array<string> $columns
     * @return static|null
     */
    public function nth(int $index, ?array $columns = null): ?static
    {
        $this->setSelectColumns($columns);
        $this->run();

        $data = $this->values();


        $totalItems = count($data);
        $idx =  abs($index);

        if ($totalItems < $idx || $index == 0) {
            return null;
        }

        if ($index > 0) {
            $result = $data[$index - 1];
        } else {
            $result = $data[$this->count() + $index];
        }

        return $this->processOutput($result);
    }

    /**
     * sorting from prepared data
     *
     * @param string $column
     * @param string $order
     * @return QueryEngine
     */
    public function sortBy(string $column, string $order = 'asc'): static
    {
        $this->run();

        usort($this->_data, function ($a, $b) use ($column, $order) {
            $val1 = $this->arrayGet($a, $column);
            $val2 = $this->arrayGet($b, $column);
            if (is_string($val1)) {
                $val1 = strtolower($val1);
            }

            if (is_string($val2)) {
                $val2 = strtolower($val2);
            }

            if ($val1 == $val2) {
                return 0;
            }
            $order = strtolower(trim($order));

            if ($order == 'desc') {
                return ($val1 > $val2) ? -1 : 1;
            } else {
                return ($val1 < $val2) ? -1 : 1;
            }
        });

        return $this;
    }

    /**
     * Sort an array value
     *
     * @param string $order
     * @return QueryEngine
     */
    public function sort(string $order = 'asc'): static
    {
        $order = strtolower($order);

        if ($order == 'desc') {
            rsort($this->_data);
        }else{
            sort($this->_data);
        }

        return $this->processOutput($this->_data);

    }

    /**
     * getting data from desire path
     *
     * @param string $path
     * @return mixed
     */
    public function grab(string $path): mixed
    {
        $this->_conditions = [];

        return $this->from($path)->raw();
    }

    /**
     * Get the raw data of result
     *
     * @return mixed
     */
    public function result(): mixed
    {
        return $this->_data;
    }

    /**
     * take action of each element of prepared data
     *
     * @param callable $fn
     * @throws ConditionNotAllowedException
     */
    public function each(callable $fn): void
    {
        $this->run();

        foreach ($this->_data as $key => $val) {
            $fn($val, $key);
        }
    }

    /**
     * transform prepared data by using callable function
     *
     * @param callable $fn
     * @return static
     * @throws ConditionNotAllowedException
     */
    public function transform(callable $fn): static
    {
        $this->run();
        $data = [];

        foreach ($this->_data as $key => $val) {
            $data[$key] = $fn($val);
        }

        return $this->processOutput($data);
    }


    /**
     * map prepared data by using callable function for each entity
     *
     * @param callable $fn
     * @return QueryEngine
     */
    public function map(callable $fn): static
    {
        $this->run();
        $data = [];

        foreach ($this->_data as $key => $val) {
            $data[] = $fn($key, $val);
        }

        return $this->processOutput($data, true);
    }

    /**
     * filtered each element of prepared data
     *
     * @param callable $fn
     * @param bool $keepIndexes
     * @return static
     */
    public function filter(callable $fn, bool $keepIndexes = false): static
    {
        $this->run();

        $data = [];
        foreach ($this->_data as $k => $val) {
            if ($fn($val)) {
                if ($keepIndexes) {
                    $data[$k] = $val;
                } else {
                    $data[] = $val;
                }
            }
        }

        return $this->processOutput($data);
    }

    /**
     * then method set position of working data
     *
     * @param string $node
     * @return QueryEngine
     */
    public function then(string $node = '.'): static
    {
        $this->run();
        $this->_isProcessed = false;

        $this->from($node);

        return $this;
    }

    /**
     * implode resulting data from desire key and delimeter
     *
     * @param string|array $key
     * @param string $delimiter
     * @return static
     * @throws ConditionNotAllowedException|\Exception
     */
    public function implode(string|array $key, string $delimiter = ','): static
    {
        $this->run();

        $implode = [];
        if (is_string($key)) {
            $implodedData[$key] = $this->makeImplode($key, $delimiter);
            return $this->processOutput($implodedData);
        }

        if (is_array($key)) {
            foreach ($key as $k) {
                $imp = $this->makeImplode($k, $delimiter);
                $implode[$k] = $imp;
            }

           return $this->processOutput($implode);
        }

        $implodedData[$key] = '';
        return $this->processOutput($implodedData);
    }

    /**
     * process implode from resulting data
     *
     * @param string $key
     * @param string $delimiter
     * @return string
     */
    protected function makeImplode(string $key, string $delimiter = ','): string
    {
        $data = array_column($this->toArray(), $key);

        return implode($delimiter, $data);
    }

    /**
     * getting specific key's value from prepared data
     *
     * @param string $column
     * @param string|null $index
     * @return static
     */
    public function column(string $column, ?string $index = null): static
    {
        $this->run();

        $data = array_column($this->_data, $column, $index);
        return $this->processOutput($data);
    }

    /**
     * getting raw JSON from prepared data
     *
     * @return string
     */
    public function toJson(): string
    {
        $this->run();

        return json_encode($this->toArray());
    }

    /**
     * @return mixed
     */
    public function toArray(): array
    {
        $this->run();
        $maps = $this->_data;

        return convert_to_array($maps);
    }

    /**
     * getting all keys from prepared data
     *
     * @return QueryEngine
     */
    public function keys(): static
    {
        $this->run();

        return $this->processOutput(array_keys($this->_data));
    }

    /**
     * getting all values from prepared data
     *
     * @return QueryEngine
     */
    public function values(): static
    {
        $this->run();

        return $this->processOutput(array_values($this->_data));
    }

    /**
     * getting chunk values from prepared data
     *
     * @param int $amount
     * @param callable|null $fn
     * @return static
     */
    public function chunk(int $amount, ?callable $fn = null): ?static
    {
        $this->run();

        $this->_data = array_chunk($this->_data, $amount);

        if (!is_null($fn) && is_callable($fn)) {
            foreach ($this as $chunk) {
                $fn($chunk);
            }

            return null;
        }

        return $this->processOutput($this->_data);
    }

    /**
     * Pluck is the alias of column
     *
     * @param string $column
     * @param ?string $key
     * @return static
     */
    public function pluck(string $column, ?string $key = null): static
    {
        return $this->column($column, $key);
    }

    /**
     * Array pop from current result set
     *
     * @return static
     */
    public function pop(): static
    {
        $this->run();
        $data = array_pop($this->_data);

        return $this->processOutput($data, true);
    }

    /**
     * Array shift from current result set
     *
     * @return static
     */
    public function shift(): static
    {
        $this->run();
        $data = array_shift($this->_data);

        return $this->processOutput($data, true);
    }

    /**
     * Push the given data in current result set
     *
     * @param mixed $data
     * @param string|null $key
     * @return static
     */
    public function push(mixed $data, ?string $key = null): static
    {
        $this->run();

        if (is_null($key)) {
            $this->_data[] = $data;
        } else {
            $this->_data[$key] = $data;
        }

        return $this;
    }
}
