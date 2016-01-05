<?php

include __DIR__ . '/../vendor/autoload.php';

$minifier = new s9e\WebServices\Minifier\Minifier;
$minifier->cacheDir    = __DIR__ . '/cache/';
$minifier->maxPayload  = 300000;
$minifier->mustContain = 's9e';
$minifier->handleRequest();