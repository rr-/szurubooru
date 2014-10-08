<?php
namespace Szurubooru\Services;
use Szurubooru\Helpers\HttpHelper;

class NetworkingService
{
	private $httpHelper;

	public function __construct(HttpHelper $httpHelper)
	{
		$this->httpHelper = $httpHelper;
	}

	public function serveFile($fullPath, $options = [])
	{
		$daysToLive = isset($options->daysToLive)
			? $options->daysToLive
			: 7;

		$secondsToLive = $daysToLive * 24 * 60 * 60;
		$lastModified = filemtime($fullPath);
		$eTag = md5(file_get_contents($fullPath)); //todo: faster

		$ifModifiedSince = isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])
			? $_SERVER['HTTP_IF_MODIFIED_SINCE']
			: false;

		$eTagHeader = isset($_SERVER['HTTP_IF_NONE_MATCH'])
			? trim($_SERVER['HTTP_IF_NONE_MATCH'], "\" \t\r\n")
			: null;

		$this->httpHelper->setHeader('ETag', '"' . $eTag . '"');
		$this->httpHelper->setHeader('Last-Modified', gmdate('D, d M Y H:i:s \G\M\T', $lastModified));
		$this->httpHelper->setHeader('Pragma', 'public');
		$this->httpHelper->setHeader('Cache-Control', 'public, max-age=' . $secondsToLive);
		$this->httpHelper->setHeader('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + $secondsToLive));

		if (isset($options->customFileName))
			$this->httpHelper->setHeader('Content-Disposition', 'inline; filename="' . $options->customFileName . '"');

		$this->httpHelper->setHeader(
			'Content-Type',
			isset($options->mimeType)
				? $options->mimeType
				: mime_content_type($fullPath));

		if (strtotime($ifModifiedSince) === $lastModified or $eTagHeader === $eTag)
		{
			$this->httpHelper->setResponseCode(304);
		}
		else
		{
			$this->httpHelper->setResponseCode(200);
			readfile($fullPath);
		}
		exit;
	}

	public function download($url, $maxBytes = null)
	{
		set_time_limit(60);
		try
		{
			$srcHandle = fopen($url, 'rb');
		}
		catch (Exception $e)
		{
			throw new \Exception('Cannot open URL for reading: ' . $e->getMessage());
		}

		if (!$srcHandle)
			throw new \Exception('Cannot open URL for reading');

		$result = '';
		try
		{
			while (!feof($srcHandle))
			{
				$buffer = fread($srcHandle, 4 * 1024);
				if ($maxBytes !== null and strlen($result) > $maxBytes)
				{
					throw new \Exception(
						'File is too big (maximum size: %s)',
						TextHelper::useBytesUnits($maxBytes));
				}
				$result .= $buffer;
			}
		}
		finally
		{
			fclose($srcHandle);
		}

		return $result;
	}

	public function redirect($destination)
	{
		$this->httpHelper->setResponseCode(307);
		$this->httpHelper->setHeader('Location', $destination);
	}

	public function nonCachedRedirect($destination)
	{
		$this->httpHelper->setResponseCode(303);
		$this->httpHelper->setHeader('Location', $destination);
	}
}
