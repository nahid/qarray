<?php

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use Nahid\QArray\Exceptions\FileNotFoundException;
use Nahid\QArray\Exceptions\InvalidJsonException;
use Nahid\QArray\ValueNotFound;

trait Queriable
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
     * contains column names for except
     * @var array
     */
    protected $_except = [];

    /**
     * Stores base contents.
     *
     * @var array
     */
    protected $_baseData = [];

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
    protected static $_rulesMap = [
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
        'null' => 'isNull',
        'notnull' => 'isNotNull',
        'startswith' => 'startWith',
        'endswith' => 'endWith',
        'match' => 'match',
        'contains' => 'contains',
        'dates' => 'dateEqual',
        'month' => 'monthEqual',
        'year' => 'yearEqual',
        'instance'  => 'instance',
        'any'  => 'any',
    ];


    /**
     * import data from file
     *
     * @param string|null $file
     * @return bool
     * @throws FileNotFoundException
     * @throws InvalidJsonException
     */
    public function import($file = null)
    {
        if (!is_null($file)) {
            if (is_string($file) && file_exists($file)) {
                $this->_data = $this->getDataFromFile($file);
                $this->_baseData = $this->_data;
                return true;
            }
        }

        throw new FileNotFoundException();
    }

    /**
     * Prepare data from desire conditions
     *
     * @return $this
     * @throws ConditionNotAllowedException
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

            $this->_data = $this->objectToArray($calculatedData);

            $this->_conditions = [];
            $this->_node = '';
            $this->_isProcessed = true;
            return $this;
        }

        $this->_isProcessed = true;
        if (!is_null($this->_take)) {
            $this->_data = array_slice($this->_data, $this->_offset, $this->_take);
        }

        $this->_data = $this->objectToArray($this->getData());
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


    protected function isCollection($array)
    {
        if (!is_array($array)) return false;

        return array_keys($array) === range(0, count($array) - 1);
    }

    /**
     * Check given value is valid JSON
     *
     * @param string $value
     * @param bool $isReturnData
     *
     * @return bool|array
     */
    public function isJson($value, $isReturnData = false)
    {
        if (is_array($value) || is_object($value)) {
            return false;
        }

        $data = json_decode($value, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }

        return $isReturnData ? $data : true;
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
     * Prepare data for result
     *
     * @param mixed $data
     * @return array|mixed
     */
    protected function prepareResult($data)
    {
        $output = [];

        if (is_null($data) || is_scalar($data)) {
            $this->_data = $data;
            return $this;
        }

        if (!is_array($data)) {
            $this->_data = $data;

            return $this;
        }

        foreach ($data as $key => $val) {
            $output[$key] = $this->generateResultData($val);
        }

        $this->_data = $output;

        return $this;
    }

    protected function generateResultData($data)
    {
        $output = $data;

        if (is_array($data)) {
            $output = $this->instanceWithValue($data, ['_select' => $this->_select, '_except' => $this->_except]);
        }

        return $output;
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
        $instance->fresh($meta);
        $value = $instance->takeColumn($value);
        return $instance->collect($value);
    }

    /**
     * Read JSON data from file
     *
     * @param string $file
     * @param string $type
     * @return bool|string|array
     * @throws FileNotFoundException
     * @throws InvalidJsonException
     */
    protected function getDataFromFile($file, $type = 'application/json')
    {
        if (file_exists($file)) {
            $opts = [
                'http' => [
                    'header' => 'Content-Type: '.$type.'; charset=utf-8',
                ],
            ];

            $context = stream_context_create($opts);
            $data = file_get_contents($file, 0, $context);
            $json = $this->isJson($data, true);

            if (!$json) {
                throw new InvalidJsonException();
            }

            return $json;
        }

        throw new FileNotFoundException();
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
     * @param $data array
     * @param $node string
     * @return bool|array|mixed
     */
    protected function getFromNested($data, $node)
    {
        if (empty($node) || $node == $this->_traveler) {
            return $data;
        }

        if (!$node) return new ValueNotFound();

        $terminate = false;
        $path = explode($this->_traveler, $node);

        foreach ($path as $val) {
            if (!is_array($data)) return $data;

            if (!array_key_exists($val, $data)) {
                $terminate = true;
                break;
            }

            $data = &$data[$val];
        }

        if ($terminate) {
            return new ValueNotFound();
        }

        return $data;
    }

    /**
     * get data from node path
     *
     * @return mixed
     */
    protected function getData()
    {
        return $this->getFromNested($this->_data, $this->_node);
    }

    /**
     * @return array
     */
    protected function processQuery()
    {
        $data = $this->getData();
        $conditions = $this->_conditions;

        return array_filter($data, function ($data) use ($conditions) {
            return $this->applyConditions($conditions, $data);
        });
    }

    /**
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
            $value = $this->getFromNested($data, $rule['key']);

            if (!is_callable($rule['condition'])) {
                $function = $this->makeConditionalFunctionFromOperator($rule['condition']);
                $params = [$value, $rule['value']];
            }

            if (is_callable($rule['condition'])) {
                $function = $rule['condition'];
                $params = [$value, $data];
            }

            if ($value instanceof ValueNotFound) {
                $andDecision = false;
            }

            if (! $value instanceof ValueNotFound) {
                $andDecision = call_user_func_array($function, $params);
            }

            //$andDecision = $value instanceof ValueNotFound ? false :  call_user_func_array($function, [$value, $rule['value']]);
            $orDecision &= $andDecision;
        }

        return $orDecision;

    }

    /**
     * @param $condition
     * @return array
     * @throws ConditionNotAllowedException
     */
    protected function makeConditionalFunctionFromOperator($condition)
    {
        if (!isset(self::$_rulesMap[$condition])) {
            throw new ConditionNotAllowedException("Exception: {$condition} condition not allowed");
        }

        $function = self::$_rulesMap[$condition];
        if (!is_callable($function)) {
            if (!method_exists(Condition::class, $function)) {
                throw new ConditionNotAllowedException("Exception: {$condition} condition not allowed");
            }

            $function = [Condition::class, $function];
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
        if (!is_null($condition) && !is_callable($condition) && is_null($value)) {
            $value = $condition;
            $condition = '=';
        }

        if (is_callable($condition) && is_null($value)) {
            $value = null;
        }

        if (count($this->_conditions) < 1) {
            array_push($this->_conditions, []);
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

        return $this->makeWhere($key, $condition, $value);
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
     * @return $this
     */
    public function whereBool($key = null, $value)
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
    public function whereNotNull($key = null)
    {
        $this->where($key, 'notnull', 'null');

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
        return $this->where($key, function($columnValue, $row) use ($value, $condition) {
            $columnValue = date('Y-m-d', strtotime($columnValue));

            $function = $this->makeConditionalFunctionFromOperator($condition);

            return call_user_func_array($function, [$columnValue, $value]);
        });
    }

    /**
     * make WHERE month clause
     *
     * @param string $key
     * @param mixed $condition
     * @param string $value
     * @return $this
     */
    public function whereMonth($key, $condition, $value)
    {
        return $this->where($key, function($columnValue, $row) use ($value, $condition) {
            $columnValue = date('m', strtotime($columnValue));

            $function = $this->makeConditionalFunctionFromOperator($condition);

            return call_user_func_array($function, [$columnValue, $value]);
        });
    }

    /**
     * make WHERE Year clause
     *
     * @param string $key
     * @param mixed $condition
     * @param string $value
     * @return $this
     */
    public function whereYear($key, $condition, $value)
    {
        return $this->where($key, function($columnValue, $row) use ($value, $condition) {
            $columnValue = date('Y', strtotime($columnValue));

            $function = $this->makeConditionalFunctionFromOperator($condition);

            return call_user_func_array($function, [$columnValue, $value]);
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
        if (!array_key_exists($name, self::$_rulesMap)) {
            self::$_rulesMap[$name] = $fn;
            return true;
        }

        return false;
    }
}
