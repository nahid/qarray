<?php

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use Nahid\QArray\Exceptions\InvalidNodeException;
use Nahid\QArray\Exceptions\KeyNotPresentException;
use function DeepCopy\deep_copy;

class Clause
{
    /**
     * store node path
     * @var string|array
     */
    protected $_node = '';

    /**
     * contain prepared data for process
     * @var mixed
     */
    protected $_data;

    /**
     * contains column names
     * @var array
     */
    protected $_select = [];

    /**
     * @var int
     */
    protected $_offset = 0;

    /**
     * @var null
     */
    protected $_take = null;


    /**
     * contains column names for except
     * @var array
     */
    protected $_except = [];

    /**
     * Stores base contents.
     *
     * @var array
     */
    protected $_original = [];

    /**
     * Stores all conditions.
     *
     * @var array
     */
    protected $_conditions = [];

    /**
     * @var bool
     */
    protected $_isProcessed = false;

    /**
     * @var string
     */
    protected $_traveler = '.';

    /**
     * map all conditions with methods
     * @var array
     */
    protected static $_conditionsMap = [
        '=' => 'equal',
        'eq' => 'equal',
        '==' => 'strictEqual',
        'seq' => 'strictEqual',
        '!=' => 'notEqual',
        'neq' => 'notEqual',
        '!==' => 'strictNotEqual',
        'sneq' => 'strictNotEqual',
        '>' => 'greaterThan',
        'gt' => 'greaterThan',
        '<' => 'lessThan',
        'lt' => 'lessThan',
        '>=' => 'greaterThanOrEqual',
        'gte' => 'greaterThanOrEqual',
        '<=' => 'lessThanOrEqual',
        'lte' => 'lessThanOrEqual',
        'in'    => 'in',
        'notin' => 'notIn',
        'inarray' => 'inArray',
        'notinarray' => 'notInArray',
        'null' => 'isNull',
        'notnull' => 'isNotNull',
        'exists' => 'exists',
        'notexists' => 'notExists',
        'startswith' => 'startWith',
        'endswith' => 'endWith',
        'match' => 'match',
        'contains' => 'contains',
        'dates' => 'dateEqual',
        'instance'  => 'instance',
        'any'  => 'any',
    ];

    /**
     * @param array $props
     * @return $this
     */
    public function fresh($props = [])
    {
        $properties = [
            '_data'  => [],
            '_original' => [],
            '_select' => [],
            '_isProcessed' => false,
            '_node' => '',
            '_except' => [],
            '_conditions' => [],
            '_take' => null,
            '_offset' => 0,
            '_traveler' => '.',
        ];

        foreach ($properties as $property=>$value) {
            if (isset($props[$property])) {
                $value = $props[$property];
            }

            $this->$property = $value;
        }

        return $this;
    }


    /**
     * import parsed data from raw json
     *
     * @param array|object $data
     * @return self
     */
    public function collect($data)
    {
        $this->reProcess();
        $this->fresh();

        $this->_data = deep_copy($data);
        $this->_original = deep_copy($data);

        return $this;
    }


    /**
     * Prepare data from desire conditions
     *
     * @return $this
     */
    protected function prepare()
    {
        if ($this->_isProcessed) {
            return $this;
        }

        if (count($this->_conditions) > 0) {
            $calculatedData = $this->processQuery();
            if (!is_null($this->_take)) {
                $calculatedData = array_slice($calculatedData, $this->_offset, $this->_take);
            }

            $this->_data = $calculatedData;

            $this->_isProcessed = true;
            return $this;
        }

        $this->_isProcessed = true;
        if (!is_null($this->_take)) {
            $this->_data = array_slice($this->_data, $this->_offset, $this->_take);
        }

        $this->_data = $this->getData();

        return $this;
    }

    /**
     * Our system will cache processed data and prevend multiple time processing. If
     * you want to reprocess this method can help you
     *
     * @return $this
     */
    public function reProcess()
    {
        $this->_isProcessed = false;
        return $this;
    }

    /**
     * Parse object to array
     *
     * @param object $obj
     * @return array|mixed
     */
    protected function objectToArray($obj)
    {
        if (!is_array($obj) && !is_object($obj)) {
            return $obj;
        }

        if (is_array($obj)) {
            return $obj;
        }

        if (is_object($obj)) {
            $obj = get_object_vars($obj);
        }

        return array_map([$this, 'objectToArray'], $obj);
    }

    /**
     * Check given value is multidimensional array
     *
     * @param array $arr
     * @return bool
     */
    protected function isMultiArray($arr)
    {
        if (!is_array($arr)) {
            return false;
        }

        rsort($arr);

        return isset($arr[0]) && is_array($arr[0]);
    }


    /**
     * Check the given array is a collection
     *
     * @param $array
     * @return bool
     */
    protected function isCollection($array)
    {
        if (!is_array($array)) return false;

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Set node path, where QArray start to prepare
     *
     * @param null $node
     * @return $this
     * @throws InvalidNodeException
     */
    public function from($node = null)
    {
        $this->_isProcessed = false;

        if (is_null($node) || $node == '') {
            throw new InvalidNodeException();
        }

        $this->_node = $node;

        return $this;
    }

    /**
     * Taking desire columns from result
     *
     * @param $array
     * @return array
     */
    public function takeColumn($array)
    {
        return $this->selectColumn($this->exceptColumn($array));
    }

    /**
     * selecting specific column
     *
     * @param $array
     * @return array
     */
    protected function selectColumn($array)
    {
        $keys = $this->_select;
        if (count($keys) == 0) {
            return $array;
        }

        $select = array_keys($keys);
        $columns = array_intersect_key($array, array_flip((array) $select));
        $row = [];
        foreach ($columns as $column => $val) {
            $fn = null;
            if (array_key_exists($column, $keys)) {
                $fn = $keys[$column];
            }

            if (is_callable($fn)) {
                $val = call_user_func_array($fn, [$val, $array]);
            }

            $row[$column] = $val;
        }

        return $row;
    }


    /**
     * selecting specific column
     *
     * @param $array
     * @return array
     */
    protected function exceptColumn($array)
    {
        $keys = $this->_except;

        if (count($keys) == 0) {
            return $array;
        }

        return array_diff_key($array, array_flip((array) $keys));
    }


    /**
     * select desired column
     *
     * @param array $columns
     * @return $this
     */
    public function select($columns = [])
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        $this->setSelect($columns);

        return $this;
    }

    /**
     * setter for select columns
     *
     * @param array $columns
     */
    protected function setSelect($columns = [])
    {
        if (count($columns) <= 0 ) {
            return;
        }

        foreach ($columns as $key => $column) {
            if (is_string($column)) {
                $this->_select[$column] = $key;
            } elseif(is_callable($column)) {
                $this->_select[$key] = $column;
            } else {
                $this->_select[$column] = $key;
            }
        }
    }

    /**
     * Set offset value for slice of array
     *
     * @param $offset
     * @return $this
     */
    public function offset($offset)
    {
        $this->_offset = $offset;

        return $this;
    }

    /**
     * Set taken value for slice of array
     *
     * @param $take
     * @return $this
     */
    public function take($take)
    {
        $this->_take = $take;

        return $this;
    }


    /**
     * select desired column for except
     *
     * @param array $columns
     * @return $this
     */
    public function except($columns = [])
    {
        if (!is_array($columns)) {
            $columns = func_get_args();
        }

        if (count($columns) > 0 ){
            $this->_except = $columns;
        }

        return $this;
    }


    /**
     * Prepare data for result
     *
     * @param mixed $data
     * @param bool $newInstance
     * @return array|mixed
     */
    protected function makeResult($data, $newInstance = false)
    {
        if (!$newInstance || is_null($data) || is_scalar($data) || !is_array($data)) {
            $this->_data = $data;
            return $this;
        }

        /*
        foreach ($data as $key => $val) {
            $output[$key] = $this->generateResultData($val);
        }*/


        return $this->instanceWithValue($data, ['_select' => $this->_select, '_except' => $this->_except]);
    }

    /**
     * Create/Copy new instance with given value
     *
     * @param       $value
     * @param array $meta
     * @return mixed
     */
    protected function instanceWithValue($value, $meta = [])
    {
        $instance = new static();
        $instance = $instance->collect($value);
        $instance->fresh($meta);

        return $instance;
    }

    /**
     * Set traveler delimiter
     *
     * @param $delimiter
     * @return $this
     */
    public function setTraveler($delimiter)
    {
        $this->_traveler = $delimiter;

        return $this;
    }

    /**
     * Get data from nested array
     *
     * @param array $data
     * @param string $node
     * @param mixed $default
     * @return mixed
     */
    protected function arrayGet($data, $node, $default = null)
    {
        if (empty($node) || $node == $this->_traveler) {
            return $data;
        }

        if (!$node) return new KeyNotExists();

        if (isset($data[$node])) {
            return $data[$node];
        }

        if (strpos($node, $this->_traveler) === false) {
            return $default;
        }

        $items = $data;

        foreach (explode($this->_traveler, $node) as $segment) {
            if (!is_array($items) || !isset($items[$segment])) {
                return $default;
            }

            $items = &$items[$segment];
        }

        return $items;
    }
    /**
     * get data from node path
     *
     * @return mixed
     */
    protected function getData()
    {
        return $this->arrayGet($this->_data, $this->_node);
    }

    /**
     * Process the given queries
     *
     * @return array
     * @throws ConditionNotAllowedException
     */
    protected function processQuery()
    {
        $_data = $this->getData();
        $conditions = $this->_conditions;

        /*return array_filter($data, function ($data) use ($conditions) {
            return $this->applyConditions($conditions, $data);
        });*/

        $result = [];
        if (!is_array($_data)) return null;

        foreach ($_data as $key => $data) {
            $keep = $this->applyConditions($conditions, $data);
            if ($keep) {
                $result[$key] = $this->takeColumn($data);
            }
        }

        return $result;
    }

    /**
     * All the given conditions applied here
     *
     * @param $conditions
     * @param $data
     * @return bool
     * @throws ConditionNotAllowedException
     */
    protected function applyConditions($conditions, $data)
    {
        $decision = false;
        foreach ($conditions as $cond) {
            $orDecision = true;
            $this->processEachCondition($cond, $data, $orDecision);
            $decision |= $orDecision;
        }

        return $decision;
    }

    /**
     * Apply every conditions for each row
     *
     * @param $rules
     * @param $data
     * @param $orDecision
     * @return bool|mixed
     * @throws ConditionNotAllowedException
     */
    protected function processEachCondition($rules, $data, &$orDecision)
    {
        if (!is_array($rules)) return false;

        $andDecision = true;

        foreach ($rules as $rule) {
            $params = [];
            $function = null;

            $value = $this->arrayGet($data, $rule['key']);

            if (!is_callable($rule['condition'])) {
                $function = $this->makeConditionalFunctionFromOperator($rule['condition']);
                $params = [$value, $rule['value']];
            }

            if (is_callable($rule['condition'])) {
                $function = $rule['condition'];
                $params = [$data];
            }

            if ($value instanceof KeyNotExists) {
                $andDecision = false;
            }

            $andDecision = call_user_func_array($function, $params);

           /*
            if (! $value instanceof KeyNotExists) {
                $andDecision = call_user_func_array($function, $params);
            }*/

            //$andDecision = $value instanceof KeyNotExists ? false :  call_user_func_array($function, [$value, $rule['value']]);
            $orDecision &= $andDecision;
        }

        return $orDecision;

    }

    /**
     * Build or generate a function for applies condition from operator
     * @param $condition
     * @return array
     * @throws ConditionNotAllowedException
     */
    protected function makeConditionalFunctionFromOperator($condition)
    {
        if (!isset(self::$_conditionsMap[$condition])) {
            throw new ConditionNotAllowedException("Exception: {$condition} condition not allowed");
        }

        $function = self::$_conditionsMap[$condition];
        if (!is_callable($function)) {
            if (!method_exists(ConditionFactory::class, $function)) {
                throw new ConditionNotAllowedException("Exception: {$condition} condition not allowed");
            }

            $function = [ConditionFactory::class, $function];
        }

        return $function;
    }

    /**
     * make WHERE clause
     *
     * @param string $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    public function where($key, $condition = null, $value = null)
    {
        if (!is_null($condition) && is_null($value)) {
            $value = $condition;
            $condition = '=';
        }

        if (count($this->_conditions) < 1) {
            array_push($this->_conditions, []);
        }

        if (is_callable($key)) {
            $key($this);

            return $this;
        }

        return $this->makeWhere($key, $condition, $value);
    }

    /**
     * make WHERE clause with OR
     *
     * @param string $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    public function orWhere($key = null, $condition = null, $value = null)
    {
        if (!is_null($condition) && is_null($value)) {
            $value = $condition;
            $condition = '=';
        }

        array_push($this->_conditions, []);

        if (is_callable($key)) {
            $key($this);

            return $this;
        }

        return $this->makeWhere($key, $condition, $value);
    }

    /**
     * make a callable where condition for custom logic implementation
     *
     * @param callable $fn
     * @return $this
     */
    public function callableWhere(callable $fn)
    {
        if (count($this->_conditions) < 1) {
            array_push($this->_conditions, []);
        }

        return $this->makeWhere(null, $fn, null);
    }

    /**
     * make a callable orwhere condition for custom logic implementation
     *
     * @param callable $fn
     * @return $this\
     */
    public function orCallableWhere(callable $fn)
    {
        array_push($this->_conditions, []);
        $fn($this);
        return $this;
        //return $this->makeWhere(null, $fn, null);
    }

    /**
     * generator for AND and OR where
     *
     * @param string $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    protected function makeWhere($key, $condition = null, $value = null)
    {
        $current = end($this->_conditions);
        $index = key($this->_conditions);

        array_push($current, [
            'key' => $key,
            'condition' => $condition,
            'value' => $value
        ]);

        $this->_conditions[$index] = $current;
        $this->_isProcessed = false;

        return $this;
    }

    /**
     * make WHERE IN clause
     *
     * @param string $key
     * @param array $value
     * @return $this
     */
    public function whereIn($key = null, $value = [])
    {
        $this->where($key, 'in', $value);

        return $this;
    }

    /**
     * make WHERE NOT IN clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereNotIn($key = null, $value = [])
    {
        $this->where($key, 'notin', $value);
        return $this;
    }

    /**
     * check the given value are contains in the given array key
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function whereInArray($key, $value)
    {
        $this->where($key, 'inarray', $value);
        return $this;
    }

    /**
     * make a callable wherenot condition for custom logic implementation
     *
     * @param $key
     * @param $value
     * @return $this
     */
    public function whereNotInArray($key, $value)
    {
        $this->where($key, 'notinarray', $value);
        return $this;
    }

    /**
     * make WHERE NULL clause
     *
     * @param string $key
     * @return $this
     */
    public function whereNull($key = null)
    {
        $this->where($key, 'null', 'null');
        return $this;
    }


    /**
     * make WHERE Boolean clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereBool($key, $value)
    {
        if (is_bool($value)) {
            $this->where($key, '==', $value);
        }
        return $this;
    }

    /**
     * make WHERE NOT NULL clause
     *
     * @param string $key
     * @return $this
     */
    public function whereNotNull($key)
    {
        $this->where($key, 'notnull', 'null');

        return $this;
    }

    /**
     * Check the given key is exists in row
     *
     * @param $key
     * @return $this
     */
    public function whereExists($key)
    {
        $this->where($key, 'exists', 'null');

        return $this;
    }

    /**
     * Check the given key is not exists in row
     *
     * @param $key
     * @return $this
     */
    public function whereNotExists($key)
    {
        $this->where($key, 'notexists', 'null');

        return $this;
    }

    /**
     * make WHERE START WITH clause
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function whereStartsWith($key, $value)
    {
        $this->where($key, 'startswith', $value);

        return $this;
    }

    /**
     * make WHERE ENDS WITH clause
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function whereEndsWith($key, $value)
    {
        $this->where($key, 'endswith', $value);

        return $this;
    }

    /**
     * make WHERE MATCH clause
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function whereMatch($key, $value)
    {
        $this->where($key, 'match', $value);

        return $this;
    }

    /**
     * make WHERE CONTAINS clause
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function whereContains($key, $value)
    {
        $this->where($key, 'contains', $value);

        return $this;
    }

    /**
     * make WHERE LIKE clause
     *
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function whereLike($key, $value)
    {
        $this->where($key, 'contains', strtolower($value));

        return $this;
    }

    /**
     * make WHERE DATE clause
     *
     * @param string $key
     * @param string $condition
     * @param string $value
     * @return $this
     */
    public function whereDate($key, $condition, $value = null)
    {
        return $this->callableWhere(function($row) use($key, $condition, $value) {
            $haystack = isset($row[$key]) ? $row[$key] : null;
            $haystack = date('Y-m-d', strtotime($haystack));

            $function = $this->makeConditionalFunctionFromOperator($condition);

            return call_user_func_array($function, [$haystack, $value]);
        });
    }

    /**
     * make WHERE Instance clause
     *
     * @param string $key
     * @param object|string $object
     * @return $this
     */
    public function whereInstance($key, $object)
    {
        $this->where($key, 'instance', $object);

        return $this;
    }

    /**
     * make WHERE any clause
     *
     * @param string $key
     * @param mixed
     * @return $this
     */
    public function whereAny($key, $value)
    {
        $this->where($key, 'any', $value);

        return $this;
    }
    /**
     * make WHERE any clause
     *
     * @param string $key
     * @param mixed
     * @param mixed
     * @return $this
     */
    public function whereCount($key, $condition, $value = null)
    {
        return $this->where($key, function($columnValue, $row) use ($value, $condition) {
            $count = 0;
            if (is_array($columnValue)) {
                $count = count($columnValue);
            }

            $function = $this->makeConditionalFunctionFromOperator($condition);

            return call_user_func_array($function, [$count, $value]);
        });
    }

    /**
     * make macro for custom where clause
     *
     * @param string $name
     * @param callable $fn
     * @return bool
     */
    public static function macro($name, callable $fn)
    {
        if (!array_key_exists($name, self::$_conditionsMap)) {
            self::$_conditionsMap[$name] = $fn;
            return true;
        }

        return false;
    }
}
