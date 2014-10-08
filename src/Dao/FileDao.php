<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\IFileDao;

class FileDao implements IFileDao
{
	private $directory;

	public function __construct($directory)
	{
		$this->directory = $directory;
	}

	public function load($fileName)
	{
		$fullPath = $this->getFullPath($fileName);
		return file_exists($fullPath)
			? file_get_contents($fullPath)
			: null;
	}

	public function save($fileName, $data)
	{
		$fullPath = $this->getFullPath($fileName);
		$this->createFolders($fileName);
		file_put_contents($fullPath, $data);
	}

	public function delete($fileName)
	{
		$fullPath = $this->getFullPath($fileName);
		if (file_exists($fullPath))
			unlink($fullPath);
	}

	public function exists($fileName)
	{
		$fullPath = $this->getFullPath($fileName);
		return file_exists($fullPath);
	}

	public function getFullPath($fileName)
	{
		return $this->directory . DIRECTORY_SEPARATOR . $fileName;
	}

	private function createFolders($fileName)
	{
		$fullPath = $this->getFullPath(dirname($fileName));
		if (!file_exists($fullPath))
			mkdir($fullPath, 0777, true);
	}
}
