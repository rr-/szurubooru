<?php
namespace Szurubooru\Helpers;

class HttpHelper
{
	public function setResponseCode($code)
	{
		http_response_code($code);
	}

	public function setHeader($key, $value)
	{
		header("$key: $value");
	}

	public function output($data)
	{
		echo $data;
	}

	public function outputJSON($data)
	{
		$this->output(json_encode((array) $data));
	}

	public function getRequestHeaders()
	{
		return getallheaders();
	}

	public function getRequestHeader($key)
	{
		$headers = $this->getRequestHeaders();
		return isset($headers[$key]) ? $headers[$key] : null;
	}

	public function getRequestMethod()
	{
		return $_SERVER['REQUEST_METHOD'];
	}

	public function getRequestUri()
	{
		$requestUri = $_SERVER['REQUEST_URI'];
		$requestUri = preg_replace('/\?.*$/', '', $requestUri);
		return $requestUri;
	}

	public function redirect($destination)
	{
		$this->setResponseCode(307);
		$this->setHeader('Location', $destination);
	}

	public function nonCachedRedirect($destination)
	{
		$this->setResponseCode(303);
		$this->setHeader('Location', $destination);
	}
}
