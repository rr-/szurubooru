<?php
namespace Szurubooru\Tests;

abstract class AbstractTestCase extends \PHPUnit_Framework_TestCase
{
	public function mock($className)
	{
		return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
	}
}
