<?php

declare(strict_types=1);

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use Nahid\QArray\Exceptions\KeyNotPresentException;
use function DeepCopy\deep_copy;

abstract class QueryEngine extends Clause implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * this constructor read data from file and parse the data for query
     *
     * @param ?string $data
     */
    public function __construct(?string $data = null)
    {
        if ((is_file($data) && file_exists($data)) || filter_var($data, FILTER_VALIDATE_URL)) {
            $this->collect($this->readPath($data));

        } else {
            $this->collect($this->parseData($data));
        }
    }

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
     * @param mixed $key
     * @param mixed $val
     */
    public function __set(mixed $key, mixed $val): void
    {
        $this->_data[$key] = $val;
    }

    /**
     * @return array
     */
    public function __invoke(): array
    {
        return $this->toArray();
    }

    /**
     * Implementation of ArrayAccess : check existence of the target offset
     *
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->_data[$offset]);
    }

    /**
     * Implementation of ArrayAccess : Get the target offset
     *
     * @param mixed $offset
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
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->_data[$offset] = $value;
    }

    /**
     * Implementation of ArrayAccess : Unset the target offset
     *
     * @param mixed $offset
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
     * @return mixed
     */
    public function key(): mixed
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
     * @return QueryEngine
     */
    public function copy(bool $fresh = false): self
    {
        if ($fresh) {
            $this->fresh([
                '_data' => $this->_data,
                '_original' => $this->_original,
                '_traveler' => $this->_traveler,
            ]);
        }

        return deep_copy($this);
    }

    /**
     * Alias of from() method
     *
     * @param string $node
     * @return $this
     */
    public function at(string $node = '.'): self
    {
        return $this->from($node);
    }

    /**
     * getting prepared data
     *
     * @param string ...$columns
     * @return QueryEngine
     */
    public function get(string ...$columns): self
    {

        $this->setSelect($columns);
        $this->prepare();
        return $this->makeResult($this->_data);
    }

    /**
     * getting prepared data
     *
     * @param string ...$columns
     * @return mixed
     */
    public function receive(string ...$columns): mixed
    {
        $this->setSelect($columns);

        return $this->prepareForReceive();
    }

    /**
     * alias of get method
     *
     * @param string ...$columns
     * @return QueryEngine
     */
    public function fetch(string ...$columns): self
    {
        return $this->get(...$columns);
    }

    /**
     * check exists data from the query
     *
     * @return bool
     */
    public function exists(): bool
    {
        $this->prepare();

        return (!empty($this->_data));
    }

    /**
     * reset given data to the $_data
     *
     * @param mixed $data
     * @param bool $fresh
     * @return QueryEngine
     */
    public function reset(array $data = [], bool $fresh = false): self
    {
        if ($data === []) {
            $data = deep_copy($this->_original);
        }

        if ($fresh) {
            $self = new static();
            $self->collect($data);

            return $self;
        }

        $this->collect($data);

        return $this;
    }

    /**
     * getting group data from specific column
     *
     * @param string $column
     * @return $this
     */
    public function groupBy(string $column): self
    {
        $this->prepare();

        $data = [];
        foreach ($this->_data as $map) {
            $value = $this->arrayGet($map, $column);
            if ($value) {
                $data[$value][] = $map;
            }
        }

        $this->_data = $data;
        return $this;
    }

    /**
     * Group by count from array value
     *
     * @param string $column
     * @return $this
     */
    public function countGroupBy(string $column): self
    {

        $this->prepare();

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
     * @return $this
     */
    public function distinct(string $column): self
    {
        $this->prepare();

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
        $this->prepare();

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
     * sum prepared data
     * @param string|null $column
     * @return int|float
     */
    public function sum(?string $column = null): int|float
    {
        $this->prepare();
        $data = $this->_data;

        $sum = 0;
        if (is_null($column)) {
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
        $this->prepare();
        $data = $this->_data;
        if (!is_null($column)) {
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
        $this->prepare();
        $data = $this->_data;

        if (!is_null($column)) {
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
        $this->prepare();

        $count = $this->count();
        $total = $this->sum($column);

        return ($total/$count);
    }

    /**
     * getting first element of prepared data
     *
     * @param array $columns
     * @return QueryEngine|null
     */
    public function first(string ...$columns): ?self
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($columns);

        if (count($data) > 0) {
            $data = $this->toArray();
            $this->_data = reset($data);
            return $this;
        }

        return null;
    }

    /**
     * getting last element of prepared data
     *
     * @param array $columns
     * @return QueryEngine|null
     */
    public function last(string ...$columns): ?self
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($columns);

        if (count($data) > 0) {
            return $this->makeResult(end($data));
        }

        return null;
    }

    /**
     * getting nth number of element of prepared data
     *
     * @param int $index
     * @param array $columns
     * @return QueryEngine|null
     */
    public function nth(int $index, string ...$columns): ?self
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($columns);

        $total_elm = count($data);
        $idx =  abs($index);

        if ($total_elm < $idx || $index == 0) {
            return null;
        }

        if ($index > 0) {
            $result = $data[$index - 1];
        } else {
            $result = $data[$this->count() + $index];
        }

        return $this->makeResult($result);
    }

    /**
     * sorting from prepared data
     *
     * @param string $column
     * @param string $order
     * @return QueryEngine
     */
    public function sortBy(string $column, string $order = 'asc'): self
    {
        $this->prepare();

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
    public function sort(string $order = 'asc'): self
    {
        $this->_data = convert_to_array($this->_data);

        if ($order == 'desc') {
            rsort($this->_data);
        }else{
            sort($this->_data);
        }

        return $this->makeResult($this->_data);

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

        return $this->from($path)->receive();
    }

    /**
     * @param int $index
     * @param string|null $column
     * @return $this|self|null
     */
    public function find(int $index, ?string $column = null): ?self
    {
        $this->prepare();

        $data = array_values($this->_data);

        if (array_is_list($data) && is_null($column) && isset($data[$index])) {
            $this->_data = $data[$index];

            return $this;
        }

        if (!$this->isCollection($data)) {
            return null;
        }

        foreach ($data as $key => $val) {
            if ($this->arrayGet($val, $column) == $index) {
                $this->_data = $val;

                return $this;
            }
        }

        return null;
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
        $this->prepare();

        foreach ($this->_data as $key => $val) {
            $fn($key, $val);
        }
    }

    /**
     * transform prepared data by using callable function
     *
     * @param callable $fn
     * @return self
     * @throws ConditionNotAllowedException
     */
    public function transform(callable $fn): self
    {
        $this->prepare();
        $data = [];

        foreach ($this->_data as $key => $val) {
            $data[$key] = $fn($val);
        }

        return $this->makeResult($data);
    }


    /**
     * map prepared data by using callable function for each entity
     *
     * @param callable $fn
     * @return QueryEngine
     */
    public function map(callable $fn): self
    {
        $this->prepare();
        $data = [];
        
        foreach ($this->_data as $key => $val) {
            $data[] = $fn($key, $val);
        }
        
        return $this->makeResult($data);
    }

    /**
     * filtered each element of prepared data
     *
     * @param callable $fn
     * @param bool $key
     * @return QueryEngine
     */
    public function filter(callable $fn, bool $key = false): self
    {
        $this->prepare();

        $data = [];
        foreach ($this->_data as $k => $val) {
            if ($fn($val)) {
                if ($key) {
                    $data[$k] = $val;
                } else {
                    $data[] = $val;
                }
            }
        }

        return $this->makeResult($data);
    }

    /**
     * then method set position of working data
     *
     * @param string $node
     * @return QueryEngine
     */
    public function then(string $node = '.'): self
    {
        $this->prepare();
        $this->from($node);

        return $this;
    }

    /**
     * implode resulting data from desire key and delimeter
     *
     * @param string|array $key
     * @param string $delimiter
     * @return self
     * @throws ConditionNotAllowedException|\Exception
     */
    public function implode(string|array $key, string $delimiter = ','): self
    {
        $this->prepare();

        $implode = [];
        if (is_string($key)) {
            $implodedData[$key] = $this->makeImplode($key, $delimiter);
            return $this->makeResult($implodedData);
        }

        if (is_array($key)) {
            foreach ($key as $k) {
                $imp = $this->makeImplode($k, $delimiter);
                $implode[$k] = $imp;
            }

           return $this->makeResult($implode);
        }

        $implodedData[$key] = '';
        return $this->makeResult($implodedData);
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
     * @return self
     */
    public function column(string $column, ?string $index = null): self
    {
        $this->prepare();

        $data = array_column($this->_data, $column, $index);
        return $this->makeResult($data);
    }

    /**
     * getting raw JSON from prepared data
     *
     * @return string
     */
    public function toJson(): string
    {
        $this->prepare();

        return json_encode($this->toArray());
    }

    /**
     * @return mixed
     */
    public function toArray(): array
    {
        $this->prepare();
        $maps = $this->_data;

        return convert_to_array($maps);
    }

    /**
     * getting all keys from prepared data
     *
     * @return QueryEngine
     */
    public function keys(): self
    {
        $this->prepare();

        return $this->makeResult(array_keys($this->_data));
    }

    /**
     * getting all values from prepared data
     *
     * @return QueryEngine
     */
    public function values(): self
    {
        $this->prepare();

        return $this->makeResult(array_values($this->_data));
    }

    /**
     * getting chunk values from prepared data
     *
     * @param int $amount
     * @param callable|null $fn
     * @return array
     */
    public function chunk(int $amount, ?callable $fn = null): array
    {
        $this->prepare();

        $chunk_value = array_chunk($this->_data, $amount);
        $chunks = [];

        if (!is_null($fn) && is_callable($fn)) {
            foreach ($chunk_value as $chunk) {
                $return = $fn($chunk);
                if (!is_null($return)) {
                    $chunks[] = $return;
                }
            }
            return count($chunks) > 0 ? $chunks : [];
        }

        return $chunk_value;
    }

    /**
     * Pluck is the alias of column
     *
     * @param string $column
     * @param null $key
     * @return QueryEngine
     */
    public function pluck(string $column, mixed $key = null): self
    {
        return $this->column($column, $key);
    }

    /**
     * Array pop from current result set
     *
     * @return QueryEngine
     */
    public function pop(): self
    {
        $this->prepare();
        $data = array_pop($this->_data);

        return $this->makeResult($data);
    }

    /**
     * Array shift from current result set
     *
     * @return QueryEngine
     */
    public function shift(): self
    {
        $this->prepare();
        $data = array_shift($this->_data);

        return $this->makeResult($data);
    }

    /**
     * Push the given data in current result set
     *
     * @param mixed $data
     * @param string|null $key
     * @return $this
     */
    public function push(mixed $data, ?string $key = null): self
    {
        $this->prepare();

        if (is_null($key)) {
            $this->_data[] = $data;
        } else {
            $this->_data[$key] = $data;
        }

        return $this;
    }
}
