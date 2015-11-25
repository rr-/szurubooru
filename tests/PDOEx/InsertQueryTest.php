<?php
namespace Szurubooru\Tests\PDOEx;
use Szurubooru\PDOEx\InsertQuery;
use Szurubooru\PDOEx\PDOEx;
use Szurubooru\Tests\AbstractTestCase;

final class InsertQueryTest extends AbstractTestCase
{
    public function testDefault()
    {
        $query = $this->getInsertQuery();
        $query->values(['key1' => 'value', 'key2' => 'value2']);
        $this->assertRegExp('/^INSERT INTO test \(key1, key2\) VALUES \(:\w*, :\w*\)$/', $query->getQuery());
    }

    private function getInsertQuery()
    {
        $pdoMock = $this->mock(PDOEx::class);
        return new InsertQuery($pdoMock, 'test');
    }
}
