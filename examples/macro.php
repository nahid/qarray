<?php
/**
 * Example: sum($column = null)
 * ================================
 *
 * sum() method return summation of resulting data
 */

require_once '../vendor/autoload.php';

use Nahid\JsonQ\Query;

$q = new Query('data1.json');

$q->macro('dateGt', function($val, $comp) {
    $date_split = explode('/', $val);
    $date_format = $date_split[2]. '-' . $date_split[0] . '-' . $date_split[1];
    $comp = strtotime($comp);
    $date = strtotime($date_format);
    return $date > $comp;
});

try {
    $res = $q
        ->from('disclosures')
        ->where('eventDate', 'dateGt', '2018-07-02')
        ->get();

    dump($res);
} catch (\Nahid\JsonQ\Exceptions\ConditionNotAllowedException $e) {
    echo $e->getMessage();
} catch (\Nahid\JsonQ\Exceptions\NullValueException $e) {
    echo $e->getMessage();
}
