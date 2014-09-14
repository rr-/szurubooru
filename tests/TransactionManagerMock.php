<?php
namespace Szurubooru\Tests;

class TransactionManagerMock extends \Szurubooru\Dao\TransactionManager
{
	public function rollback($callback)
	{
		return $callback();
	}

	public function commit($callback)
	{
		return $callback();
	}
}
