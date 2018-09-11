<?php

require '../vendor/autoload.php';

$fileHandle = fopen('count.php', 'w+')
OR die ("Can't open file\n");
$fileData = fread ($fileHandle, 1024);
dump($fileData);
fclose ($fileHandle);

