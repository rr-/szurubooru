<?php
namespace Szurubooru\Tests;

final class ConfigTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $testDirectory;
	private $baseConfigFilePath;
	private $localConfigFilePath;

	public function setUp()
	{
		parent::setUp();
		$this->testDirectory = $this->createTestDirectory();
		$this->baseConfigFilePath = $this->testDirectory . DIRECTORY_SEPARATOR . 'config.ini';
		$this->localConfigFilePath = $this->testDirectory . DIRECTORY_SEPARATOR . 'local.ini';
	}

	public function testReadingNonSections()
	{
		file_put_contents($this->baseConfigFilePath, 'test=value');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals('value', $config->test);
	}

	public function testReadingUnnestedSections()
	{
		file_put_contents($this->baseConfigFilePath, '[test]' . PHP_EOL . 'key=value');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals('value', $config->test->key);
	}

	public function testReadingNestedSections()
	{
		file_put_contents($this->baseConfigFilePath, '[test.subtest]' . PHP_EOL . 'key=value');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals('value', $config->test->subtest->key);
	}

	public function testReadingMultipleNestedSections()
	{
		file_put_contents(
			$this->baseConfigFilePath,
			'[test.subtest]' . PHP_EOL . 'key=value' . PHP_EOL .
				'[test.subtest.deeptest]' . PHP_EOL . 'key=zombie');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals('value', $config->test->subtest->key);
		$this->assertEquals('zombie', $config->test->subtest->deeptest->key);
	}

	public function testReadingNonExistentFiles()
	{
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals(0, count(iterator_to_array($config->getIterator())));
	}

	public function testMultipleFiles()
	{
		file_put_contents($this->baseConfigFilePath, 'test=trash');
		file_put_contents($this->localConfigFilePath, 'test=overridden');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertEquals('overridden', $config->test);
	}

	public function testReadingUnexistingProperties()
	{
		file_put_contents($this->baseConfigFilePath, 'meh=value');
		$config = new \Szurubooru\Config($this->testDirectory);
		$this->assertNull($config->unexistingSection);
	}

	public function testOverwritingValues()
	{
		file_put_contents($this->baseConfigFilePath, 'meh=value');
		$config = new \Szurubooru\Config($this->testDirectory);
		$config->newKey = 'fast';
		$this->assertEquals('fast', $config->newKey);
	}
}
