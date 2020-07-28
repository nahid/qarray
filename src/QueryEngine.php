<?php

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use Nahid\QArray\Exceptions\InvalidNodeException;
use Nahid\QArray\Exceptions\KeyNotPresentException;
use function DeepCopy\deep_copy;

abstract class QueryEngine extends Clause implements \ArrayAccess, \Iterator, \Countable
{
    /**
     * this constructor read data from file and parse the data for query
     *
     * @param string $data
     */
    public function __construct($data = null)
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
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * @param string $path
     * @return array
     */
    public abstract function readPath($path);

    /**
     * @param string $data
     * @return array
     */
    public abstract function parseData($data);

    /**
     * @param $key
     * @return mixed
     * @throws KeyNotPresentException
     */
    public function __get($key)
    {
        if (isset($this->_data[$key]) or is_null($this->_data[$key])) {
            return $this->_data[$key];
        }

        throw new KeyNotPresentException();
    }

    /**
     * Property override for current object
     *
     * @param $key
     * @param $val
     */
    public function __set($key, $val)
    {
        if (is_array($this->_data)) {
            $this->_data[$key] = $val;
        }
    }

    /**
     * @return mixed
     * @throws ConditionNotAllowedException
     */
    public function __invoke()
    {
        return $this->toArray();
    }

    /**
     * Implementation of ArrayAccess : check existence of the target offset
     *
     * @param mixed $key
     * @return bool
     */
    public function offsetExists($key)
    {
        return isset($this->_data[$key]);
    }

    /**
     * Implementation of ArrayAccess : Get the target offset
     *
     * @param mixed $key
     * @return mixed|KeyNotExists
     */
    public function offsetGet($key)
    {
        if ($this->offsetExists($key)) {
            return $this->_data[$key];
        }

        return new KeyNotExists();
    }

    /**
     * Implementation of ArrayAccess : Set the target offset
     *
     * @param mixed $key
     * @param mixed $value
     */
    public function offsetSet($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * Implementation of ArrayAccess : Unset the target offset
     *
     * @param mixed $key
     */
    public function offsetUnset($key)
    {
        if ($this->offsetExists($key)) {
           unset($this->_data[$key]);
        }
    }

    /**
     * Implementation of Iterator : Rewind the Iterator to the first element
     *
     * @return mixed|void
     */
    public function rewind()
    {
        return reset($this->_data);
    }

    /**
     * Implementation of Iterator : Return the current element
     * @return mixed
     */
    public function current()
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
     * @return int|mixed|null|string
     */
    public function key()
    {
        return key($this->_data);
    }

    /**
     * Implementation of Iterator : Move forward to next element
     *
     * @return mixed|void
     */
    public function next()
    {
        return next($this->_data);
    }

    /**
     * Implementation of Iterator : Checks if current position is valid
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->_data) !== null;
    }

    /**
     * Deep copy current instance
     *
     * @param bool $fresh
     * @return QueryEngine
     */
    public function copy($fresh = false)
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
     * @param null $node
     * @return $this
     * @throws InvalidNodeException
     */
    public function at($node = null)
    {
        return $this->from($node);
    }

    /**
     * getting prepared data
     *
     * @param array $column
     * @return QueryEngine
     * @throws ConditionNotAllowedException
     */
    public function get($column = [])
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        $this->setSelect($column);
        $this->prepare();
        return $this->makeResult($this->_data);
    }

    /**
     * alias of get method
     *
     * @param array $column
     * @return array|object
     * @throws ConditionNotAllowedException
     */
    public function fetch($column = [])
    {
        if (!is_array($column)) {
            $column = func_get_args();
        }

        return $this->get($column);
    }

    /**
     * check exists data from the query
     *
     * @return bool
     * @throws ConditionNotAllowedException
     */
    public function exists()
    {
        $this->prepare();

        return (!empty($this->_data) && !is_null($this->_data));
    }

    /**
     * reset given data to the $_data
     *
     * @param mixed $data
     * @param bool $fresh
     * @return QueryEngine
     */
    public function reset($data = null, $fresh = false)
    {
        if (is_null($data)) {
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
     * @throws ConditionNotAllowedException
     */
    public function groupBy($column)
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
     * @param $column
     * @return $this
     * @throws ConditionNotAllowedException
     */
    public function countGroupBy($column)
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
     * @throws ConditionNotAllowedException
     */
    public function distinct($column)
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
     * @throws ConditionNotAllowedException
     */
    public function count()
    {
        $this->prepare();

        return count($this->_data);
    }

    /**
     * size is an alias of count
     *
     * @return int
     * @throws ConditionNotAllowedException
     */
    public function size()
    {
        return $this->count();
    }

    /**
     * sum prepared data
     * @param int $column
     * @return number
     * @throws ConditionNotAllowedException
     */
    public function sum($column = null)
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
     * @param int $column
     * @return number
     * @throws ConditionNotAllowedException
     */
    public function max($column = null)
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
     * @param int $column
     * @return number
     * @throws ConditionNotAllowedException
     */
    public function min($column = null)
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
     * @param int $column
     * @return number
     * @throws ConditionNotAllowedException
     */
    public function avg($column = null)
    {
        $this->prepare();

        $count = $this->count();
        $total = $this->sum($column);

        return ($total/$count);
    }

    /**
     * getting first element of prepared data
     *
     * @param array $column
     * @return object|array|null
     * @throws ConditionNotAllowedException
     */
    public function first($column = [])
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($column);

        if (!is_array($data)) return null;

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
     * @param array $column
     * @return object|array|null
     * @throws ConditionNotAllowedException
     */
    public function last($column = [])
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($column);

        if (!is_array($data)) return null;

        if (count($data) > 0) {
            return $this->makeResult(end($data));
        }

        return null;
    }

    /**
     * getting nth number of element of prepared data
     *
     * @param int $index
     * @param array $column
     * @return object|array|null
     * @throws ConditionNotAllowedException
     */
    public function nth($index, $column = [])
    {
        $this->prepare();

        $data = $this->_data;
        $this->setSelect($column);

        if (!is_array($data)) return null;

        $total_elm = count($data);
        $idx =  abs($index);

        if (!is_integer($index) || $total_elm < $idx || $index == 0 || !is_array($this->_data)) {
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
     * @return object|array|null
     * @throws ConditionNotAllowedException
     */
    public function sortBy($column, $order = 'asc')
    {
        $this->prepare();

        if (!is_array($this->_data)) {
            return $this;
        }

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
    public function sort($order = 'asc')
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
     * @param array $column
     * @return mixed
     * @throws InvalidNodeException
     * @throws ConditionNotAllowedException
     */
    public function find($path, $column = [])
    {
        return $this->from($path)->prepare()->get($column);
    }

    /**
     * Get the raw data of result
     *
     * @return mixed
     */
    public function result()
    {
        return $this->_data;
    }

    /**
     * take action of each element of prepared data
     *
     * @param callable $fn
     * @throws ConditionNotAllowedException
     */
    public function each(callable $fn)
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
    public function transform(callable $fn)
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
     * @return object|array
     * @throws ConditionNotAllowedException
     */
    public function map(callable $fn)
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
     * @return mixed|array
     * @throws ConditionNotAllowedException
     */
    public function filter(callable $fn, $key = false)
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
     * @throws InvalidNodeException
     * @throws ConditionNotAllowedException
     */
    public function then($node)
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
     * @throws ConditionNotAllowedException
     */
    public function implode($key, $delimiter = ',')
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
     * @return string|null
     * @throws \Exception
     */
    protected function makeImplode($key, $delimiter = ',')
    {
        $data = array_column($this->toArray(), $key);

        if (is_array($data)) {
            return implode($delimiter, $data);
        }

        return '';
    }

    /**
     * getting specific key's value from prepared data
     *
     * @param string $column
     * @param string|null $index
     * @return self
     * @throws ConditionNotAllowedException
     */
    public function column($column, $index = null)
    {
        $this->prepare();

        $data = array_column($this->_data, $column, $index);
        return $this->makeResult($data);
    }

    /**
     * getting raw JSON from prepared data
     *
     * @return string
     * @throws ConditionNotAllowedException
     */
    public function toJson()
    {
        $this->prepare();

        return json_encode($this->toArray());
    }

    /**
     * @return mixed
     * @throws ConditionNotAllowedException
     */
    public function toArray()
    {
        $this->prepare();
        $maps = $this->_data;
        return convert_to_array($maps);
    }

    /**
     * getting all keys from prepared data
     *
     * @return object|array
     * @throws ConditionNotAllowedException
     */
    public function keys()
    {
        $this->prepare();

        return $this->makeResult(array_keys($this->_data));
    }

    /**
     * getting all values from prepared data
     *
     * @return object|array
     * @throws ConditionNotAllowedException
     */
    public function values()
    {
        $this->prepare();

        return $this->makeResult(array_values($this->_data));
    }

    /**
     * getting chunk values from prepared data
     *
     * @param int $amount
     * @param callable $fn
     * @return object|array|bool
     * @throws ConditionNotAllowedException
     */
    public function chunk($amount, callable $fn = null)
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
            return count($chunks) > 0 ? $chunks : null;
        }

        return $chunk_value;
    }

    /**
     * Pluck is the alias of column
     *
     * @param $column
     * @param null $key
     * @return array|mixed|QueryEngine
     */
    public function pluck($column, $key = null)
    {
        return $this->column($column, $key);
    }

    /**
     * Array pop from current result set
     *
     * @return array|mixed|QueryEngine
     */
    public function pop()
    {
        $this->prepare();

        $data = array_pop($this->_data);
        return $this->makeResult($data);
    }

    /**
     * Array shift from current result set
     *
     * @return array|mixed|QueryEngine
     */
    public function shift()
    {
        $this->prepare();

        $data = array_shift($this->_data);
        return $this->makeResult($data);
    }

    /**
     * Push the given data in current result set
     *
     * @param $data
     * @param null $key
     * @return $this
     */
    public function push($data, $key = null)
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
