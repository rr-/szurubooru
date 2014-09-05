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
}

date_default_timezone_set('UTC');
