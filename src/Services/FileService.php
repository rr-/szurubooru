<?php
namespace Szurubooru\Services;

class FileService
{
	private $dataDirectory;
	private $httpHelper;

	public function __construct($dataDirectory, \Szurubooru\Helpers\HttpHelper $httpHelper)
	{
		$this->dataDirectory = $dataDirectory;
		$this->httpHelper = $httpHelper;
	}

	public function serve($source, $options = [])
	{
		$finalSource = $this->getFullPath($source);

		$daysToLive = isset($options->daysToLive)
			? $options->daysToLive
			: 7;

		$secondsToLive = $daysToLive * 24 * 60 * 60;
		$lastModified = filemtime($finalSource);
		$eTag = md5(file_get_contents($finalSource)); //todo: faster

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
				: mime_content_type($finalSource));

		if (strtotime($ifModifiedSince) === $lastModified or $eTagHeader === $eTag)
		{
			$this->httpHelper->setResponseCode(304);
		}
		else
		{
			$this->httpHelper->setResponseCode(200);
			readfile($finalSource);
		}
		exit;
	}

	public function createFolders($target)
	{
		$finalTarget = $this->getFullPath($target);
		if (!file_exists($finalTarget))
			mkdir($finalTarget, 0777, true);
	}

	public function exists($source)
	{
		$finalSource = $this->getFullPath($source);
		return $source and file_exists($finalSource);
	}

	public function delete($source)
	{
		$finalSource = $this->getFullPath($source);
		if (file_exists($finalSource))
			unlink($finalSource);
	}

	public function saveFromBase64($base64string, $destination)
	{
		$finalDestination = $this->getFullPath($destination);
		$commaPosition = strpos($base64string, ',');
		if ($commaPosition !== null)
			$base64string = substr($base64string, $commaPosition + 1);
		$data = base64_decode($base64string);
		file_put_contents($finalDestination, $data);
	}

	public function getFullPath($destination)
	{
		return $this->dataDirectory . DIRECTORY_SEPARATOR . $destination;
	}
}
