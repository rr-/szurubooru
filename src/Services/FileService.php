<?php
namespace Szurubooru\Services;

class FileService
{
	private $dataDirectory;
	private $httpHelper;

	public function __construct(\Szurubooru\Config $config, \Szurubooru\Helpers\HttpHelper $httpHelper)
	{
		$this->dataDirectory = $config->getDataDirectory();
		$this->httpHelper = $httpHelper;
	}

	public function serve($target, $options = [])
	{
		$fullPath = $this->getFullPath($target);

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

	public function createFolders($target)
	{
		$fullPath = $this->getFullPath($target);
		if (!file_exists($fullPath))
			mkdir($fullPath, 0777, true);
	}

	public function exists($target)
	{
		$fullPath = $this->getFullPath($target);
		return $target and file_exists($fullPath);
	}

	public function delete($target)
	{
		$fullPath = $this->getFullPath($target);
		if (file_exists($fullPath))
			unlink($fullPath);
	}

	public function save($destination, $data)
	{
		$finalDestination = $this->getFullPath($destination);
		file_put_contents($finalDestination, $data);
	}

	public function getFullPath($destination)
	{
		return $this->dataDirectory . DIRECTORY_SEPARATOR . $destination;
	}
}
