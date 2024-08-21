<?php

function mockData(): array
{
    $json = file_get_contents(__DIR__ . '/mock-data.json');

    return json_decode($json, true);

}
