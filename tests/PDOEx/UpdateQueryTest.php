<?php
namespace Szurubooru\Tests\PDOEx;
use Szurubooru\PDOEx\PDOEx;
use Szurubooru\PDOEx\UpdateQuery;
use Szurubooru\Tests\AbstractTestCase;

final class UpdateQueryTest extends AbstractTestCase
{
	public function testDefault()
	{
		$query = $this->getUpdateQuery();
		$query->set(['key1' => 'value', 'key2' => 'value2']);
		$this->assertRegExp('/^UPDATE test SET key1 = :\w*, key2 = :\w*$/', $query->getQuery());
	}

	private function getUpdateQuery()
	{
		$pdoMock = $this->mock(PDOEx::class);
		return new UpdateQuery($pdoMock, 'test');
	}
}
