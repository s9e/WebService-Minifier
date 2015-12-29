<?php

namespace s9e\WebServices\Minifier;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable;

// Prepare a 500 header in case anything goes wrong
header('Content-type: text/plain', true, 500);

$code = file_get_contents('php://input');
if ($code === '')
{
	http_response_code(400);
	die('No code');
}
if (strlen($code) > 300000)
{
	http_response_code(413);
	die('Payload too large');
}
if (strpos($code, 's9e') === false)
{
	http_response_code(403);
	die('Unauthorized');
}

$minifiedCode   = null;
$compressedCode = null;

$hash = strtr(base64_encode(sha1($code, true) . md5($code, true)), '+/', '-_');

$cacheFile = __DIR__ . '/cache/' . $hash . '.gz';
if (file_exists($cacheFile))
{
	$compressedCode = file_get_contents($cacheFile);
}
else
{
	include __DIR__ . '/../vendor/autoload.php';

	$minifier = new FirstAvailable(
		['ClosureCompilerApplication', __DIR__ . '/../bin/compiler.jar'],
		'ClosureCompilerService'
	);

	try
	{
		ignore_user_abort(true);

		$minifiedCode   = $minifier->minify($code);
		$compressedCode = gzencode($minifiedCode, 9);
	}
	catch (Exception $e)
	{
		die($e->getMessage());
	}

	file_put_contents($cacheFile, $compressedCode);
}

http_response_code(200);

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
{
	header('Content-encoding: gzip');
	header('Content-length: ' . strlen($compressedCode));
	die($compressedCode);
}

if (!isset($minifiedCode))
{
	$minifiedCode = gzdecode($compressedCode);
}

header('Content-length: ' . strlen($minifiedCode));
die($minifiedCode);