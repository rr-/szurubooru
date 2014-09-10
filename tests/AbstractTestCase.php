<?php
namespace Szurubooru\Tests;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
	public function mock($className)
	{
		return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
	}

	public function mockConfig($path = null)
	{
		return new ConfigMock($path);
	}

	public function createTestDirectory()
	{
		$path = $this->getTestDirectoryPath();
		if (!file_exists($path))
			mkdir($path, 0777, true);
		return $path;
	}

	protected function tearDown()
	{
		$this->cleanTestDirectory();
	}

	private function getTestDirectoryPath()
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'files';
	}

	private function cleanTestDirectory()
	{
		if (!file_exists($this->getTestDirectoryPath()))
			return;

		$dirIterator = new \RecursiveDirectoryIterator(
			$this->getTestDirectoryPath(),
			\RecursiveDirectoryIterator::SKIP_DOTS);

		$files = new \RecursiveIteratorIterator(
			$dirIterator,
			\RecursiveIteratorIterator::CHILD_FIRST);

		foreach ($files as $fileInfo)
		{
			if ($fileInfo->isDir())
				rmdir($fileInfo->getRealPath());
			else
				unlink($fileInfo->getRealPath());
		}
	}
}

date_default_timezone_set('UTC');
