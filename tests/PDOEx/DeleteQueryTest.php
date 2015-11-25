<?php
namespace Szurubooru\Tests\PDOEx;
use Szurubooru\PDOEx\DeleteQuery;
use Szurubooru\PDOEx\PDOEx;
use Szurubooru\Tests\AbstractTestCase;

final class DeleteQueryTest extends AbstractTestCase
{
    public function testDefault()
    {
        $query = $this->getDeleteQuery();
        $this->assertEquals('DELETE FROM test', $query->getQuery());
    }

    public function testBasicWhere()
    {
        $query = $this->getDeleteQuery();
        $query->where('column', 'value');
        $this->assertRegExp('/^DELETE FROM test WHERE column = :[\w]*$/', $query->getQuery());
    }

    private function getDeleteQuery()
    {
        $pdoMock = $this->mock(PDOEx::class);
        return new DeleteQuery($pdoMock, 'test');
    }
}
