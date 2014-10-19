<?php
namespace Szurubooru\Tests\PDOEx;
use Szurubooru\PDOEx\PDOEx;
use Szurubooru\PDOEx\SelectQuery;
use Szurubooru\Tests\AbstractTestCase;

final class SelectQueryTest extends AbstractTestCase
{
	public function testDefault()
	{
		$query = $this->getSelectQuery();
		$this->assertEquals('SELECT test.* FROM test', $query->getQuery());
	}

	public function testAdditionalColumns()
	{
		$query = $this->getSelectQuery();
		$query->select('SUM(1) AS sum');
		$query->select('something else');
		$this->assertEquals('SELECT test.*, SUM(1) AS sum, something else FROM test', $query->getQuery());
	}

	public function testResettingAdditionalColumns()
	{
		$query = $this->getSelectQuery();
		$query->select(null);
		$query->select('SUM(1) AS sum');
		$this->assertEquals('SELECT SUM(1) AS sum FROM test', $query->getQuery());
	}

	public function testInnerJoin()
	{
		$query = $this->getSelectQuery();
		$query->innerJoin('test2', 'test2.id = test.foreignId');
		$this->assertEquals('SELECT test.* FROM test INNER JOIN test2 ON test2.id = test.foreignId', $query->getQuery());
	}

	public function testMultipleInnerJoins()
	{
		$query = $this->getSelectQuery();
		$query->innerJoin('test2', 'test2.id = test.foreignId');
		$query->innerJoin('test3', 'test3.id = test2.foreignId');
		$this->assertEquals('SELECT test.* FROM test INNER JOIN test2 ON test2.id = test.foreignId INNER JOIN test3 ON test3.id = test2.foreignId', $query->getQuery());
	}

	public function testSelectAfterInnerJoin()
	{
		$query = $this->getSelectQuery();
		$query->innerJoin('test2', 'test2.id = test.foreignId');
		$query->select('whatever');
		$this->assertEquals('SELECT test.*, whatever FROM test INNER JOIN test2 ON test2.id = test.foreignId', $query->getQuery());
	}

	public function testBasicWhere()
	{
		$query = $this->getSelectQuery();
		$query->where('column', 'value');
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column = :[\w]*$/', $query->getQuery());
	}

	public function testMultipleWhere()
	{
		$query = $this->getSelectQuery();
		$query->where('column1', 'value1');
		$query->where('column2', 'value2');
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column1 = :\w* AND column2 = :\w*$/', $query->getQuery());
	}

	public function testManualWhere()
	{
		$query = $this->getSelectQuery();
		$query->where('column1 >= ? AND column2 <= ?', ['value1', 'value2']);
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column1 >= :\w* AND column2 <= :\w*$/', $query->getQuery());
	}

	public function testWhereNull()
	{
		$query = $this->getSelectQuery();
		$query->where('column', null);
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column IS NULL$/', $query->getQuery());
	}

	public function testResettingWhere()
	{
		$query = $this->getSelectQuery();
		$query->where('column1', 'value1');
		$query->where(null);
		$query->where('column2', 'value2');
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column2 = :\w*$/', $query->getQuery());
	}

	public function testEmptyIn()
	{
		$query = $this->getSelectQuery();
		$query->where('column', []);
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE 0$/', $query->getQuery());
	}

	public function testIn()
	{
		$query = $this->getSelectQuery();
		$query->where('column', ['val1', 'val2']);
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column IN \(:\w*, :\w*\)$/', $query->getQuery());
	}

	public function testMixedInAndWhere()
	{
		$query = $this->getSelectQuery();
		$query->where('column1', ['val1', 'val2']);
		$query->where('column2', 'val3');
		$this->assertRegExp('/^SELECT test\.\* FROM test WHERE column1 IN \(:\w*, :\w*\) AND column2 = :\w*$/', $query->getQuery());
	}

	public function testGroupBy()
	{
		$query = $this->getSelectQuery();
		$query->groupBy('test.id');
		$this->assertEquals('SELECT test.* FROM test GROUP BY test.id', $query->getQuery());
	}

	public function testGroupByAndOrderBy()
	{
		$query = $this->getSelectQuery();
		$query->groupBy('test.id');
		$query->orderBy('test.whatever');
		$this->assertEquals('SELECT test.* FROM test GROUP BY test.id ORDER BY test.whatever', $query->getQuery());
	}

	public function testOrderBy()
	{
		$query = $this->getSelectQuery();
		$query->orderBy('id DESC');
		$this->assertEquals('SELECT test.* FROM test ORDER BY id DESC', $query->getQuery());
	}

	public function testOrderByFlavor2()
	{
		$query = $this->getSelectQuery();
		$query->orderBy('id', 'DESC');
		$this->assertEquals('SELECT test.* FROM test ORDER BY id DESC', $query->getQuery());
	}

	public function testOrderByMultiple()
	{
		$query = $this->getSelectQuery();
		$query->orderBy('id', 'DESC');
		$query->orderBy('id2', 'ASC');
		$this->assertEquals('SELECT test.* FROM test ORDER BY id DESC, id2 ASC', $query->getQuery());
	}

	public function testResettingOrderBy()
	{
		$query = $this->getSelectQuery();
		$query->orderBy('id', 'DESC');
		$query->orderBy(null);
		$query->orderBy('id2', 'ASC');
		$this->assertEquals('SELECT test.* FROM test ORDER BY id2 ASC', $query->getQuery());
	}

	public function testLimit()
	{
		$query = $this->getSelectQuery();
		$query->limit(5);
		$this->assertEquals('SELECT test.* FROM test LIMIT 5', $query->getQuery());
	}

	public function testLimitWithOffset()
	{
		$query = $this->getSelectQuery();
		$query->offset(2);
		$query->limit(5);
		$this->assertEquals('SELECT test.* FROM test LIMIT 5 OFFSET 2', $query->getQuery());
	}

	public function testOffsetWithoutLimit()
	{
		$query = $this->getSelectQuery();
		$query->offset(2);
		$query->limit(null);
		$this->assertEquals('SELECT test.* FROM test', $query->getQuery());
	}

	private function getSelectQuery()
	{
		$pdoMock = $this->mock(PDOEx::class);
		return new SelectQuery($pdoMock, 'test');
	}
}
