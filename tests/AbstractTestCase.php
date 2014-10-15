<?php
namespace Szurubooru\Tests;
use Szurubooru\DatabaseConnection;
use Szurubooru\Injector;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
	protected function setUp()
	{
		Injector::init();
		date_default_timezone_set('UTC');
	}

	protected function tearDown()
	{
		$this->cleanTestDirectory();
	}

	protected function mock($className)
	{
		return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
	}

	protected function mockConfig($dataPath = null, $publicDataPath = null)
	{
		return new ConfigMock($dataPath, $publicDataPath);
	}

	protected function mockTransactionManager()
	{
		return new TransactionManagerMock($this->mock(DatabaseConnection::class));
	}

	protected function createTestDirectory()
	{
		$path = $this->getTestDirectoryPath();
		if (!file_exists($path))
			mkdir($path, 0777, true);
		return $path;
	}

	protected function getTestFile($fileName)
	{
		return file_get_contents($this->getTestFilePath($fileName));
	}

	protected function getTestFilePath($fileName)
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'test_files' . DIRECTORY_SEPARATOR . $fileName;
	}

	protected function assertEntitiesEqual($expected, $actual)
	{
		if (!is_array($expected))
		{
			$expected = [$expected];
			$actual = [$actual];
		}
		$this->assertEquals(count($expected), count($actual), 'Unmatching array sizes');
		$this->assertEquals(array_keys($expected), array_keys($actual), 'Unmatching array keys');
		foreach (array_keys($expected) as $key)
		{
			if ($expected[$key] === null)
			{
				$this->assertNull($actual[$key]);
			}
			else
			{
				$this->assertNotNull($actual[$key]);
				$expectedEntity = clone($expected[$key]);
				$actualEntity = clone($actual[$key]);
				$expectedEntity->resetLazyLoaders();
				$expectedEntity->resetMeta();
				$actualEntity->resetLazyLoaders();
				$actualEntity->resetMeta();
				$this->assertEquals($expectedEntity, $actualEntity);
			}
		}
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
