<?php
class Database
{
	protected static $pdo = null;
	protected static $queries = [];

	public static function connect($driver, $location, $user, $pass)
	{
		if (self::connected())
			throw new Exception('Database is already connected');

		$dsn = $driver . ':' . $location;
		try
		{
			self::$pdo = new PDO($dsn, $user, $pass);
			self::$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			self::$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		}
		catch (Exception $e)
		{
			self::$pdo = null;
			throw $e;
		}
	}

	public static function makeStatement(SqlQuery $sqlQuery)
	{
		try
		{
			$stmt = self::$pdo->prepare($sqlQuery->getSql());
		}
		catch (Exception $e)
		{
			throw new Exception('Problem with ' . $sqlQuery->getSql() . ' (' . $e->getMessage() . ')');
		}
		foreach ($sqlQuery->getBindings() as $key => $value)
			$stmt->bindValue(is_numeric($key) ? $key + 1 : $key, $value);
		return $stmt;
	}

	public static function disconnect()
	{
		self::$pdo = null;
	}

	public static function connected()
	{
		return self::$pdo !== null;
	}

	public static function query(SqlQuery $sqlQuery)
	{
		if (!self::connected())
			throw new Exception('Database is not connected');
		$statement = self::makeStatement($sqlQuery);
		$statement->execute();
		self::$queries []= $sqlQuery;
		return $statement;
	}

	public static function fetchOne(SqlQuery $sqlQuery)
	{
		$statement = self::query($sqlQuery);
		return $statement->fetch();
	}

	public static function fetchAll(SqlQuery $sqlQuery)
	{
		$statement = self::query($sqlQuery);
		return $statement->fetchAll();
	}

	public static function getLogs()
	{
		return self::$queries;
	}

	public static function inTransaction()
	{
		return self::$pdo->inTransaction();
	}

	public static function lastInsertId()
	{
		return self::$pdo->lastInsertId();
	}

	public static function transaction($func)
	{
		if (self::inTransaction())
		{
			return $func();
		}
		else
		{
			try
			{
				self::$pdo->beginTransaction();
				$ret = $func();
				self::$pdo->commit();
				return $ret;
			}
			catch (Exception $e)
			{
				self::$pdo->rollBack();
				throw $e;
			}
		}
	}
}
