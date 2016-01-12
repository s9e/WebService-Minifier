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
		'ClosureCompilerService' => []
	];

	/**
	* @var string If set, the unminified code must contain this string
	*/
	public $mustContain;

	/**
	* Handle the current request
	*
	* @return void
	*/
	public function handleRequest()
	{
		// Capture the output and prepare a 500 header in case anything goes wrong
		ob_start();
		header('Content-Type: application/octet-stream', true, 500);

		try
		{
			if (isset($_GET['hash']))
			{
				$this->processCacheRequest();
			}
			else
			{
				$this->processMinifierRequest();
			}
			throw new Exception('An unspecified error occured');
		}
		catch (Exception $e)
		{
			$this->sendResponse(500, $e->getMessage());
		}
	}

	/**
	* Return whether the request's client supports gzip
	*
	* @return bool
	*/
	protected function clientSupportsGzip()
	{
		return (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);
	}

	/**
	* Process the current cache request
	*
	* @return void
	*/
	protected function processCacheRequest()
	{
		if (!preg_match('(^[-\\w]{48}$)D', $_GET['hash']))
		{
			$this->sendResponse(400, 'Bad cache ID');
		}

		$filepath = $this->cacheDir . $_GET['hash'] . '.gz';
		if (!file_exists($filepath))
		{
			$this->sendResponse(404, 'Not in cache');
		}

		$content = file_get_contents($filepath);
		if ($this->clientSupportsGzip())
		{
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
		}
		else
		{
			$content = gzdecode($content);
		}

		$this->sendResponse(200, $content);
	}

	/**
	* Process the current minifier request
	*
	* @return void
	*/
	protected function processMinifierRequest()
	{
		$minifiedCode   = null;
		$compressedCode = null;

		$code = $this->getRequestBody();
		$hash = $this->getHash($code);

		$cacheFile = $this->cacheDir . $hash . '.gz';
		if (file_exists($cacheFile))
		{
			$compressedCode = file_get_contents($cacheFile);
		}
		else
		{
			ignore_user_abort(true);

			$minifiedCode   = $this->getMinifier()->minify($code);
			$compressedCode = gzencode($minifiedCode, $this->gzLevel);

			file_put_contents($cacheFile, $compressedCode);
		}

		if ($this->clientSupportsGzip())
		{
			header('Content-Encoding: gzip');
			header('Vary: Accept-Encoding');
			$this->sendResponse(200, $compressedCode);
		}

		if (!isset($minifiedCode))
		{
			$minifiedCode = gzdecode($compressedCode);
		}

		$this->sendResponse(200, $minifiedCode);
	}

	/**
	* Compute a source's hash
	*
	* @param  string $src Original source
	* @return string      48 bytes string
	*/
	protected function getHash($src)
	{
		return strtr(base64_encode(sha1($src, true) . md5($src, true)), '+/', '-_');
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
	* Get the request body, enforce the configured limitations and return it uncompressed
	*
	* @return string
	*/
	protected function getRequestBody()
	{
		$body = file_get_contents('php://input');
		if ($body === '')
		{
			$this->sendResponse(400, 'No code');
		}
		// Sniff whether the payload is gzip-encoded
		if (substr($body, 0, 2) === "\x1f\x8b")
		{
			// Only decode the max payload to avoid gzip bombs
			$body = gzdecode($body, $this->maxPayload);
		}
		if (strlen($body) >= $this->maxPayload)
		{
			$this->sendResponse(413, 'Payload too large');
		}
		if (isset($this->mustContain) && strpos($body, $this->mustContain) === false)
		{
			$this->sendResponse(403, 'Unauthorized');
		}

		return $body;
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
		header('Content-Length: ' . strlen($body), true, $status);

		echo $body;
		exit;
	}
}