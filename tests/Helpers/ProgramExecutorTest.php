<?php
namespace Szurubooru\Tests;
use Szurubooru\Helpers\ProgramExecutor;
use Szurubooru\Tests\AbstractTestCase;

final class ProgramExecutorTest extends AbstractTestCase
{
    public function testIsProgramAvailable()
    {
        $this->assertFalse(ProgramExecutor::isProgramAvailable('there_is_no_way_my_os_can_have_this_program'));
    }
}
