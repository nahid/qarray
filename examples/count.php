<?php
/**
 * Example: count()
 * ================================
 *
 * count() method return counted value from resulting data
 */

require_once '../vendor/autoload.php';

use Nahid\JsonQ\Query;

$q = new Query('data.json');

try {
    $res = $q->from('users')->count();
    dump($res);
} catch (\Nahid\JsonQ\Exceptions\ConditionNotAllowedException $e) {
    echo $e->getMessage();
} catch (\Nahid\JsonQ\Exceptions\NullValueException $e) {
    echo $e->getMessage();
}
