<?php
namespace Szurubooru\Dao;
use Szurubooru\DatabaseConnection;

class TransactionManager
{
	private $databaseConnection;

	public function __construct(DatabaseConnection $databaseConnection)
	{
		$this->databaseConnection = $databaseConnection;
	}

	public function commit($callback)
	{
		return $this->doInTransaction($callback, 'commit');
	}

	public function rollback($callback)
	{
		return $this->doInTransaction($callback, 'rollBack');
	}

	public function doInTransaction($callback, $operation)
	{
		$pdo = $this->databaseConnection->getPDO();
		if ($pdo->inTransaction())
			return $callback();

		$pdo->beginTransaction();
		try
		{
			$ret = $callback();
			$pdo->$operation();
			return $ret;
		}
		catch (\Exception $e)
		{
			$pdo->rollBack();
			throw $e;
		}
	}
}
