<?php

namespace s9e\WebServices\Minifier;

use s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable;

// Prepare a 500 header and capture the output in case anything goes wrong
header('Content-type: text/plain', true, 500);
ob_start();

function send($status, $msg)
{
	ob_end_clean();
	header('Content-length: ' . strlen($msg), true, $status);
	die($msg);
}

$code = file_get_contents('php://input');
if ($code === '')
{
	send(400, 'No code');
}
if (substr($code, 0, 2) === "\x1f\x8b")
{
	// Only decode the max payload to avoid gzip bombs
	$code = gzdecode($code, 300000);
}
if (strlen($code) >= 300000)
{
	send(413, 'Payload too large');
}
if (strpos($code, 's9e') === false)
{
	send(403, 'Unauthorized');
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
		send(500, $e->getMessage());
	}

	file_put_contents($cacheFile, $compressedCode);
}

if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
{
	header('Content-encoding: gzip');
	send(200, $compressedCode);
}

if (!isset($minifiedCode))
{
	$minifiedCode = gzdecode($compressedCode);
}

send(200, $minifiedCode);