<?php

/**
* @package   s9e\WebServices
* @copyright Copyright (c) 2015-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\WebServices\Minifier;

use Exception;
use s9e\TextFormatter\Configurator\JavaScript\Minifiers\FirstAvailable;

class Minifier
{
	/**
	* @var string Path to cache dir
	*/
	public $cacheDir = __DIR__ . '/../www/cache/';

	/**
	* @var integer Compression level at which minified code is stored/sent
	*/
	public $gzLevel = 9;

	/**
	* @var integer Max request payload
	*/
	public $maxPayload = 300000;

	/**
	* @var array
	*/
	public $minifiers = [
		'ClosureCompilerApplication' => [
			'closureCompilerBin' => __DIR__ . '/../bin/compiler.jar'
		],
//		'ClosureCompilerService' => []
	];

	/**
	* @var string If set, the unminified code must contain this string
	*/
	public $mustContain = 's9e';

	/**
	* Handle an incoming request
	*
	* @return void
	*/
	public function handleRequest()
	{
		// Prepare a 500 header and capture the output in case anything goes wrong
		header('Content-type: application/octet-stream', true, 500);
		ob_start();

		$code = file_get_contents('php://input');
		if ($code === '')
		{
			$this->sendResponse(400, 'No code');
		}
		// Sniff whether the payload is gzip-encoded
		if (substr($code, 0, 2) === "\x1f\x8b")
		{
			// Only decode the max payload to avoid gzip bombs
			$code = gzdecode($code, $this->maxPayload);
		}
		if (strlen($code) >= $this->maxPayload)
		{
			$this->sendResponse(413, 'Payload too large');
		}
		if (isset($this->mustContain) && strpos($code, $this->mustContain) === false)
		{
			$this->sendResponse(403, 'Unauthorized');
		}

		$minifiedCode   = null;
		$compressedCode = null;

		$hash = strtr(base64_encode(sha1($code, true) . md5($code, true)), '+/', '-_');

		$cacheFile = $this->cacheDir . $hash . '.gz';
		if (file_exists($cacheFile))
		{
			$compressedCode = file_get_contents($cacheFile);
		}
		else
		{
			try
			{
				ignore_user_abort(true);

				$minifiedCode   = $this->getMinifier()->minify($code);
				$compressedCode = gzencode($minifiedCode, $this->gzLevel);
			}
			catch (Exception $e)
			{
				$this->sendResponse(500, $e->getMessage());
			}

			file_put_contents($cacheFile, $compressedCode);
		}

		if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)
		{
			header('Content-encoding: gzip');
			header('Vary: Content-encoding');
			$this->sendResponse(200, $compressedCode);
		}

		if (!isset($minifiedCode))
		{
			$minifiedCode = gzdecode($compressedCode);
		}

		$this->sendResponse(200, $minifiedCode);
	}

	/**
	* Get a fully configured minifier
	*
	* @return FirstAvailable
	*/
	protected function getMinifier()
	{
		$mainMinifier = new FirstAvailable;
		foreach ($this->minifiers as $name => $props)
		{
			$minifier = $mainMinifier->add($name);
			foreach ($props as $propName => $propValue)
			{
				$minifier->$propName = $propValue;
			}
		}

		return $mainMinifier;
	}

	/**
	* Send a response and shutdown
	*
	* @param  integer $status Response status
	* @param  string  $body   Response body
	* @return void
	*/
	protected function sendResponse($status, $body)
	{
		ob_end_clean();
		header('Content-length: ' . strlen($body), true, $status);

		echo $body;
		exit;
	}
}