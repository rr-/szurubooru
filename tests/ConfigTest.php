<?php
namespace Szurubooru\Tests;

final class ConfigTest extends \Szurubooru\Tests\AbstractTestCase
{
	private static $testFileName1;
	private static $testFileName2;

	public function setUp()
	{
		self::$testFileName1 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test-config1.ini';
		self::$testFileName2 = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'test-config2.ini';
	}

	public function testReadingNonSections()
	{
		file_put_contents(self::$testFileName1, 'test=value');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertEquals('value', $config->test);
	}

	public function testReadingUnnestedSections()
	{
		file_put_contents(self::$testFileName1, '[test]' . PHP_EOL . 'key=value');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertEquals('value', $config->test->key);
	}

	public function testReadingNestedSections()
	{
		file_put_contents(self::$testFileName1, '[test.subtest]' . PHP_EOL . 'key=value');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertEquals('value', $config->test->subtest->key);
	}

	public function testReadingMultipleNestedSections()
	{
		file_put_contents(
			self::$testFileName1,
			'[test.subtest]' . PHP_EOL . 'key=value' . PHP_EOL .
				'[test.subtest.deeptest]' . PHP_EOL . 'key=zombie');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertEquals('value', $config->test->subtest->key);
		$this->assertEquals('zombie', $config->test->subtest->deeptest->key);
	}

	public function testReadingNonExistentFiles()
	{
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertEquals(0, count((array) $config));
	}

	public function testMultipleFiles()
	{
		file_put_contents(self::$testFileName1, 'test=trash');
		file_put_contents(self::$testFileName2, 'test=overridden');
		$config = new \Szurubooru\Config([self::$testFileName1, self::$testFileName2]);
		$this->assertEquals('overridden', $config->test);
	}

	public function testReadingUnexistingProperties()
	{
		file_put_contents(self::$testFileName1, 'meh=value');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$this->assertNull($config->unexistingSection);
	}

	public function testOverwritingValues()
	{
		file_put_contents(self::$testFileName1, 'meh=value');
		$config = new \Szurubooru\Config([self::$testFileName1]);
		$config->newKey = 'fast';
		$this->assertEquals('fast', $config->newKey);
	}

	protected function tearDown()
	{
		foreach ([self::$testFileName1, self::$testFileName2] as $temporaryFileName)
		{
			if (file_exists($temporaryFileName))
				unlink($temporaryFileName);
		}
	}

}
