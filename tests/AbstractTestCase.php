<?php
namespace Szurubooru\Tests;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
	public function mock($className)
	{
		return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
	}

	public function mockConfig()
	{
		return new ConfigMock();
	}

	public function getTestDirectory()
	{
		return __DIR__ . DIRECTORY_SEPARATOR . 'files';
	}

	protected function tearDown()
	{
		$this->cleanTestDirectory();
	}

	private function cleanTestDirectory()
	{
		foreach (scandir($this->getTestDirectory()) as $fn)
			if ($fn{0} != '.')
				unlink($this->getTestDirectory() . DIRECTORY_SEPARATOR . $fn);
	}
}

date_default_timezone_set('UTC');
