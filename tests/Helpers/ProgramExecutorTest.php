<?php
namespace Szurubooru\Tests;

class ProgramExecutorTest extends \Szurubooru\Tests\AbstractTestCase
{
	public function testIsProgramAvailable()
	{
		$this->assertFalse(\Szurubooru\Helpers\ProgramExecutor::isProgramAvailable('there_is_no_way_my_os_can_have_this_program'));
	}
}
