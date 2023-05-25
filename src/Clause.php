<?php

declare(strict_types=1);

namespace Nahid\QArray;

use Nahid\QArray\Exceptions\ConditionNotAllowedException;
use function DeepCopy\deep_copy;

class Clause
{
    /**
     * store node path
     * @var string
     */
    protected string $_node = '';

    /**
     * contain prepared data for process
     * @var mixed
     */
    protected array $_data;

    /**
     * contains column names
     * @var array
     */
    protected array $_select = [];

    /**
     * @var int
     */
    protected int $_offset = 0;

    /**
     * @var ?int
     */
    protected ?int $_take = null;


    /**
     * contains column names for except
     * @var array
     */
    protected array $_except = [];

    /**
     * Stores base contents.
     *
     * @var array
     */
    protected array $_original = [];

    /**
     * Stores all conditions.
     *
     * @var array
     */
    protected array $_conditions = [];

    /**
     * @var bool
     */
    protected bool $_isProcessed = false;

    /**
     * @var string
     */
    protected string $_traveler = '.';

    /**
     * map all conditions with methods
     * @var array
     */
    protected static array $_conditionsMap = [
        '=' => 'equal',
        'eq' => 'equal',
        '==' => 'strictEqual',
        'seq' => 'strictEqual',
        '!=' => 'notEqual',
        'neq' => 'notEqual',
        '<>' => 'notEqual',
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
        'type'  => 'type',
        'any'  => 'any',
        'bool' => 'isBool',
    ];

    /**
     * @param array $props
     * @return $this
     */
    public function fresh(array $props = []): self
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
     * @param array $data
     * @return self
     */
    public function collect(array $data): self
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
    protected function prepare(): self
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
     * Prepare data from desire conditions
     *
     * @return mixed
     */
    protected function prepareForReceive(): mixed
    {
        if ($this->_isProcessed) {
            return $this->_data;
        }

        if (count($this->_conditions) > 0) {
            $calculatedData = $this->processQuery();
            if (!is_null($this->_take)) {
                $calculatedData = array_slice($calculatedData, $this->_offset, $this->_take);
            }

            $_data = $calculatedData;

            $this->_isProcessed = true;

            return $_data;
        }

        $_data = $this->_data;
        $this->_isProcessed = true;
        if (!is_null($this->_take)) {
            $_data = array_slice($this->_data, $this->_offset, $this->_take);
        }

        return $this->arrayGet($_data, $this->_node);

    }

    /**
     * Our system will cache processed data and prevend multiple time processing. If
     * you want to reprocess this method can help you
     *
     * @return $this
     */
    public function reProcess(): self
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
    protected function objectToArray(object $obj)
    {
        $obj = get_object_vars($obj);

        return array_map([$this, 'objectToArray'], $obj);
    }

    /**
     * Check given value is multidimensional array
     *
     * @param array $arr
     * @return bool
     */
    protected function isMultiArray(array $arr): bool
    {
        rsort($arr);

        return isset($arr[0]) && is_array($arr[0]);
    }


    /**
     * Check the given array is a collection
     *
     * @param array $data
     * @return bool
     */
    protected function isCollection(array $data): bool
    {
        return $data !== [] && array_is_list($data) && is_array($data[0]);
    }

    /**
     * Set node path, where QArray start to prepare
     *
     * @param string $node
     * @return self
     */
    public function from(string $node = '.'): self
    {
        $this->_isProcessed = false;

        if ($node == '') {
            $node = '.';
        }

        $this->_node = $node;

        return $this;
    }

    /**
     * Taking desire columns from result
     *
     * @param array $columns
     * @return array
     */
    public function takeColumn(array $columns): array
    {
        return $this->selectColumn($this->exceptColumn($columns));
    }

    /**
     * selecting specific column
     *
     * @param array $columns
     * @return array
     */
    protected function selectColumn(array $columns): array
    {
        $keys = $this->_select;
        if (count($keys) == 0) {
            return $columns;
        }

        $select = array_keys($keys);
        $properties = array_intersect_key($columns, array_flip((array) $select));
        $row = [];
        foreach ($properties as $column => $property) {
            $fn = null;
            if (array_key_exists($column, $keys)) {
                $fn = $keys[$column];
            }

            if (is_callable($fn)) {
                $property = call_user_func_array($fn, [$property, $columns]);
            }

            $row[$column] = $property;
        }

        return $row;
    }


    /**
     * selecting specific column
     *
     * @param array $columns
     * @return array
     */
    protected function exceptColumn( array $columns): array
    {
        $keys = $this->_except;

        if (count($keys) == 0) {
            return $columns;
        }

        return array_diff_key($columns, array_flip($keys));
    }


    /**
     * select desired column
     *
     * @param array $columns
     * @return $this
     */
    public function select(string ...$columns ): self
    {
        $this->setSelect($columns);

        return $this;
    }

    /**
     * setter for select columns
     *
     * @param array $columns
     */
    protected function setSelect(array $columns = []): void
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
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset): self
    {
        $this->_offset = $offset;

        return $this;
    }

    /**
     * Set taken value for slice of array
     *
     * @param int $take
     * @return $this
     */
    public function take(int $take): self
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
    public function except(string ...$columns): self
    {
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
     * @return self
     */
    protected function makeResult(mixed $data, bool $newInstance = false): self
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
     * @param array $value
     * @param array $meta
     * @return self
     */
    protected function instanceWithValue(array $value, array $meta = []): self
    {
        $instance = new static();
        $instance = $instance->collect($value);
        $instance->fresh($meta);

        return $instance;
    }

    /**
     * Set traveler delimiter
     *
     * @param string $delimiter
     * @return self
     */
    public function setTraveler(string $delimiter): self
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
    protected function arrayGet(array $data, string $node, mixed $default = null): mixed
    {
        if ($node === '' || $node === $this->_traveler) {
            return $data;
        }

        if (isset($data[$node])) {
            return $data[$node];
        }

        if (!str_contains($node, $this->_traveler)) {
            return $default;
        }

        $items = $data;

        foreach (explode($this->_traveler, $node) as $segment) {
            if (!isset($items[$segment])) {
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
    protected function getData(): mixed
    {
        return $this->arrayGet($this->_data, $this->_node);
    }

    /**
     * Process the given queries
     *
     * @return array|null
     * @throws ConditionNotAllowedException
     */
    protected function processQuery(): ?array
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
     * @param array $conditions
     * @param array $data
     * @return bool
     * @throws ConditionNotAllowedException
     */
    protected function applyConditions(array $conditions, array $data): bool
    {
        $decision = false;
        foreach ($conditions as $cond) {
            $orDecision = true;
            $this->processEachCondition($cond, $data, $orDecision);
            $decision |= $orDecision;
        }

        return (bool) $decision;
    }

    /**
     * Apply every conditions for each row
     *
     * @param array $rules
     * @param array $data
     * @param bool $orDecision
     * @return bool
     * @throws ConditionNotAllowedException
     */
    protected function processEachCondition(array $rules, array $data, bool &$orDecision): bool
    {
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

        return (bool) $orDecision;

    }

    /**
     * Build or generate a function for applies condition from operator
     * @param string $condition
     * @return array
     * @throws ConditionNotAllowedException
     */
    protected function makeConditionalFunctionFromOperator(string $condition): array
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
     * @param string|callable $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    public function where(string|callable $key, mixed $condition = null, mixed $value = null): self
    {
        if (!is_null($condition) && is_null($value)) {
            $value = $condition;
            $condition = '=';
        }

        if (count($this->_conditions) < 1) {
            $this->_conditions[] = [];
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
     * @param string|callable $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    public function orWhere(string|callable $key, mixed $condition = null, mixed $value = null): self
    {
        if (!is_null($condition) && is_null($value)) {
            $value = $condition;
            $condition = '=';
        }

        $this->_conditions[] = [];

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
    public function callableWhere(callable $fn): self
    {
        if (count($this->_conditions) < 1) {
            $this->_conditions[] = [];
        }

        return $this->makeWhere('', $fn, null);
    }

    /**
     * make a callable orwhere condition for custom logic implementation
     *
     * @param callable $fn
     * @return $this
     */
    public function orCallableWhere(callable $fn): self

    {
        $this->_conditions[] = [];
        $fn($this);

        //return $this;
        return $this->makeWhere('', $fn, null);
    }

    /**
     * generator for AND and OR where
     *
     * @param string $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    protected function makeWhere(string $key, mixed $condition = null, mixed $value = null): self
    {
        $current = end($this->_conditions);
        $index = key($this->_conditions);

        $current[] = [
            'key' => $key,
            'condition' => $condition,
            'value' => $value
        ];

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
    public function whereIn(string $key, array $value = []): self
    {
        $this->where($key, 'in', $value);

        return $this;
    }

    /**
     * make WHERE DATA TYPE clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereDataType(string $key, mixed $value): self
    {
        $this->where($key, 'type', $value);
        
        return $this;
    }

    /**
     * make WHERE NOT IN clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereNotIn(string $key, array $value = []): self
    {
        $this->where($key, 'notin', $value);

        return $this;
    }

    /**
     * check the given value are contains in the given array key
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereInArray(string $key, mixed $value): self
    {
        $this->where($key, 'inarray', $value);

        return $this;
    }

    /**
     * make a callable wherenot condition for custom logic implementation
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereNotInArray(string $key, mixed $value): self
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
    public function whereNull(string $key): self
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
    public function whereBool(string $key): self
    {
        $this->where($key, 'bool');

        return $this;
    }

    /**
     * make WHERE NOT NULL clause
     *
     * @param string $key
     * @return $this
     */
    public function whereNotNull(string $key): self
    {
        $this->where($key, 'notnull', 'null');

        return $this;
    }

    /**
     * Check the given key is exists in row
     *
     * @param string $key
     * @return $this
     */
    public function whereExists(string $key): self
    {
        $this->where($key, 'exists', 'null');

        return $this;
    }

    /**
     * Check the given key is not exists in row
     *
     * @param string $key
     * @return $this
     */
    public function whereNotExists(string $key): self
    {
        $this->where($key, 'notexists', 'null');

        return $this;
    }

    /**
     * make WHERE START WITH clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereStartsWith(string $key, mixed $value): self
    {
        $this->where($key, 'startswith', $value);

        return $this;
    }

    /**
     * make WHERE ENDS WITH clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereEndsWith(string $key, mixed $value): self
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
    public function whereMatch(string $key, mixed $value): self
    {
        $this->where($key, 'match', $value);

        return $this;
    }

    /**
     * make WHERE CONTAINS clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereContains(string $key, mixed $value): self
    {
        $this->where($key, 'contains', $value);

        return $this;
    }

    /**
     * make WHERE LIKE clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereLike(string $key, mixed $value): self
    {
        $this->where($key, 'contains', strtolower($value));

        return $this;
    }

    /**
     * make WHERE DATE clause
     *
     * @param string $key
     * @param string $condition
     * @param mixed $value
     * @return $this
     */
    public function whereDate(string $key, string $condition, mixed $value = null): self
    {
        return $this->callableWhere(function($row) use($key, $condition, $value) {
            $haystack = $row[$key] ?? null;
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
    public function whereInstance(string $key, object|string $object): self
    {
        $this->where($key, 'instance', $object);

        return $this;
    }

    /**
     * make WHERE any clause
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function whereAny(string $key, mixed $value): self
    {
        $this->where($key, 'any', $value);

        return $this;
    }
    /**
     * make WHERE any clause
     *
     * @param string $key
     * @param mixed $condition
     * @param mixed $value
     * @return $this
     */
    public function whereCount(string $key, mixed $condition, mixed $value = null): self
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
    public static function macro(string $name, callable $fn): bool
    {
        if (!array_key_exists($name, self::$_conditionsMap)) {
            self::$_conditionsMap[$name] = $fn;
            return true;
        }

        return false;
    }
}
