# QArray - Query Engine For Array

**QArray** is query engine for PHP array data. It helps to developers querying any kind of array data with ORM like feel.

## Installation

```
composer require nahid/qarray
```

## Implementation

Since QArray is an abstract layer of code, so you have to implement your own system with a lots of pre build functionality. 

Lets start our first implementation for JSON data.

```php
class JsonQueryEngine extends \Nahid\QArray\QueryEngine
{
    public function readPath($path)
    {
        $data = file_get_contents($path);

        return json_decode($data, true);
    }

    public function parseData($data)
    {
        return json_decode($data, true);
    }
}
```

Here you see we implement a new engine for JSON data. `QueryEngine` is an abstract class, so we have to implement its two abstract function `readPath` and `parseData`
  
## Usage

Our implementation were complete, now we can use this implementation.


```php
class Product
{
    protected $query;

    public function __construct(\Nahid\QArray\QueryEngine $query)
    {
        $this->query = $query;
    }

    public function getMacbook()
    {
        try {
            return $this->query
                ->from('.')
                ->where('cat', 2)
                ->get();
        } catch (\Exception $e) {
           return false;
        }

    }
}
```

Here we develop a class for Products and this class use `JsonQueryEngine` for fetch data from JSON file.

Lets see, how to use it.

here is our JSON file `data.json`

```json
[
  {"id":1, "name":"iPhone", "cat":1, "price": 80000},
  {"id":2, "name":"macbook pro 2017", "cat": 2, "price": 210000},
  {"id":3, "name":"Redmi 3S Prime", "cat": 1, "price": 12000},
  {"id":4, "name":"Redmi 4X", "cat":1, "price": 15000},
  {"id":5, "name":"macbook air", "cat": 2, "price": 110000}
]
```

And now we read it to querying data.

```php
$data = new JsonQueryEngine('data.json');
$query = new SomethingBuilder($data);

dump($query->getMacbook()->toArray());
```

**Output**  

```
array:2 [
  1 => array:4 [
    "id" => 2
    "name" => "macbook pro 2017"
    "cat" => 2
    "price" => 210000
  ]
  4 => array:4 [
    "id" => 5
    "name" => "macbook air"
    "cat" => 2
    "price" => 110000
  ]
]
```

Pretty neat, huh?

Let's explore the full API to see what else magic this library can do for you.
Shall we?

## API

**List of API:**

* [fetch](#fetch)
* [find](#findpath)
* [at](#atpath)
* [from](#frompath)
* [select](#select)
* [except](#except)
* [then](#then)
* [collect](#collect)
* [json](#json)
* [import](#import)
* [where](#wherekey-op-val)
* [orWhere](#orwherekey-op-val)
* [whereIn](#whereinkey-val)
* [whereNotIn](#wherenotinkey-val)
* [whereNull](#wherenullkey)
* [whereNotNull](#wherenotnullkey)
* [whereStartsWith](#wherestartswithkey-val)
* [whereEndsWith](#whereendswithkey-val)
* [whereContains](#wherecontainskey-val)
* [whereMatch](#wherematch-val)
* [whereInstance](#whereinstance-val)
* [whereDataType](#wheredatatype-val)
* [sum](#sumproperty)
* [count](#count)
* [size](#size)
* [max](#maxproperty)
* [min](#minproperty)
* [avg](#avgproperty)
* [first](#first)
* [last](#last)
* [nth](#nthindex)
* [column](#column)
* [implode](#implode)
* [exists](#exists)
* [groupBy](#groupbyproperty)
* [sort](#sortorder)
* [sortBy](#sortbyproperty-order)
* [reset](#resetdata)
* [copy](#copy)
* [toJson](#tojson)
* [keys](#keys)
* [values](#values)
* [filter](#filter)
* [transform](#transform)
* [each](#each)
* [pipe](#pipe)
* [chunk](#chunk)
* [macro](#macro)


### Available operation for where clause

* `key` -- the property name of the data. Or you can pass a Function here to group multiple query inside it. See details in [example](examples/where.js)
* `val` -- value to be matched with. It can be a _int_, _string_, _bool_ or even _Function_ - depending on the `op`.
* `op` -- operand to be used for matching. The following operands are available to use:

    * `=` : For weak equality matching
    * `eq` : Same as `=`
    * `!=` : For weak not equality matching
    * `neq` : Same as `!=`
    * `==` : For strict equality matching
    * `seq` : Same as `==`
    * `!==` : For strict not equality matching
    * `sneq` : Same as `!==`
    * `>` : Check if value of given **key** in data is Greater than **val**
    * `gt` : Same as `>`
    * `<` : Check if value of given **key** in data is Less than **val**
    * `lt` : Same as `<`
    * `>=` : Check if value of given **key** in data is Greater than or Equal of **val**
    * `gte` : Same as `>=`
    * `<=` : Check if value of given **key** in data is Less than or Equal of **val**
    * `lte` : Same as `<=`
    * `null` : Check if the value of given **key** in data is **null** (`val` parameter in `where()` can be omitted for this `op`)
    * `notnull` : Check if the value of given **key** in data is **not null** (`val` parameter in `where()` can be omitted for this `op`)
    * `in` : Check if the value of given **key** in data is exists in given **val**. **val** should be a plain _Array_.
    * `notin` : Check if the value of given **key** in data is not exists in given **val**. **val** should be a plain _Array_.
    * `startswith` : Check if the value of given **key** in data starts with (has a prefix of) the given **val**. This would only works for _String_ type data.
    * `endswith` : Check if the value of given **key** in data ends with (has a suffix of) the given **val**. This would only works for _String_ type data.
    * `contains` : Check if the value of given **key** in data has a substring of given **val**. This would only works for _String_ type data.
    * `match` : Check if the value of given **key** in data has a Regular Expression match with the given **val**. The `val` parameter should be a **RegExp** for this `op`.
    * `instance` : Check it the value of given `key` in data has an instance.

**example:**

Let's say you want to find the _'users'_ who has _`id`_ of `1`. You can do it like this:

```php
$q = new Jsonq('data.json');
$res = $q->from('users')->where('id', '=', 1)->get();
```

You can add multiple _where_ conditions. It'll give the result by AND-ing between these multiple where conditions.

```php
$q = new Jsonq('data.json');
$res = $q->from('users')
->where('id', '=', 1)
->where('location', '=', 'barisal')
->get();
```

See a detail example [here](examples/where.php).

### `orWhere(key, op, val)`

Parameters of `orWhere()` are the same as `where()`. The only difference between `where()` and `orWhere()` is: condition given by the `orWhere()` method will OR-ed the result with other conditions.

For example, if you want to find the users with _id_ of `1` or `2`, you can do it like this:

```php
$q = new Jsonq('data.json');
$res = $q->from('users')
->where('id', '=', 1)
->orWhere('id', '=', 2)
->get();
```

See detail example [here](examples/or-where.php).

### `whereIn(key, val)`

* `key` -- the property name of the data
* `val` -- it should be an **Array**

This method will behave like `where(key, 'in', val)` method call.

### `whereNotIn(key, val)`

* `key` -- the property name of the data
* `val` -- it should be an **Array**

This method will behave like `where(key, 'notin', val)` method call.

### `whereNull(key)`

* `key` -- the property name of the data

This method will behave like `where(key, 'null')` or `where(key, '=', null)` method call.

### `whereNotNull(key)`

* `key` -- the property name of the data

This method will behave like `where(key, 'notnull')` or `where(key, '!=', null)` method call.

### `whereStartsWith(key, val)`

* `key` -- the property name of the data
* `val` -- it should be a String

This method will behave like `where(key, 'startswith', val)` method call.

### `whereEndsWith(key, val)`

* `key` -- the property name of the data
* `val` -- it should be a String

This method will behave like `where(key, 'endswith', val)` method call.

### `whereContains(key, val)`

* `key` -- the property name of the data
* `val` -- it should be a String

This method will behave like `where(key, 'contains', val)` method call.

### `whereDataType(key, val)`

* `key` -- the property name of the data
* `val` -- it should be a String

This method will behave like `whereDataType(key, 'type', val)` method call.

### `sum(column)`

* `column` -- the property name of the data


## Bugs and Issues

If you encounter any bugs or issues, feel free to [open an issue at
github](https://github.com/nahid/qarray/issues).

Also, you can shoot me an email to
<mailto:nahid.dns@gmail.com> for hugs or bugs.

