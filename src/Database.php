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
			self::$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}
		catch (Exception $e)
		{
			self::$pdo = null;
			throw $e;
		}
	}

	protected static function makeStatement(SqlStatement $stmt)
	{
		try
		{
			$pdoStatement = self::$pdo->prepare($stmt->getAsString());
			foreach ($stmt->getBindings() as $key => $value)
				$pdoStatement->bindValue(is_numeric($key) ? $key + 1 : ltrim($key, ':'), $value);
		}
		catch (Exception $e)
		{
			throw new Exception('Problem with ' . $stmt->getAsString() . ' (' . $e->getMessage() . ')');
		}
		return $pdoStatement;
	}

	public static function disconnect()
	{
		self::$pdo = null;
	}

	public static function connected()
	{
		return self::$pdo !== null;
	}

	public static function exec(SqlStatement $stmt)
	{
		if (!self::connected())
			throw new Exception('Database is not connected');
		$statement = self::makeStatement($stmt);
		try
		{
			$statement->execute();
		}
		catch (Exception $e)
		{
			throw new Exception('Problem with ' . $stmt->getAsString() . ' (' . $e->getMessage() . ')');
		}
		self::$queries []= $stmt;
		return $statement;
	}

	public static function fetchOne(SqlStatement $stmt)
	{
		$statement = self::exec($stmt);
		return $statement->fetch();
	}

	public static function fetchAll(SqlStatement $stmt)
	{
		$statement = self::exec($stmt);
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
