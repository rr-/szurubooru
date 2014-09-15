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

	public function mockTransactionManager()
	{
		return new TransactionManagerMock($this->mock(\Szurubooru\DatabaseConnection::class));
	}

	public function createTestDirectory()
	{
		$path = $this->getTestDirectoryPath();
		if (!file_exists($path))
			mkdir($path, 0777, true);
		return $path;
	}

	public function getTestFile($fileName)
	{
		return file_get_contents($this->getTestFilePath($fileName));
	}

	public function getTestFilePath($fileName)
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'test_files' . DIRECTORY_SEPARATOR . $fileName;
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

require_once __DIR__
	. DIRECTORY_SEPARATOR . '..'
	. DIRECTORY_SEPARATOR . 'vendor'
	. DIRECTORY_SEPARATOR . 'autoload.php';

date_default_timezone_set('UTC');
