<?php

include __DIR__ . '/../../vendor/autoload.php';

$minifier = new s9e\WebServices\Minifier\Minifier;
$minifier->cacheDir = __DIR__ . '/cache/';
$minifier->maxPayload = 2000000;
$minifier->minifiers = [
	'ClosureCompilerService' => [
		'compilationLevel' => 'SIMPLE_OPTIMIZATIONS',
		'timeout'          => 60
	]
];
$minifier->mustContain = null;
$minifier->handleRequest();