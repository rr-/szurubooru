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

	protected static function convertStatement(SqlStatement $stmt)
	{
		try
		{
			$stmtText = $stmt->getAsString();
			$stmtPdo = self::$pdo->prepare($stmtText);
			foreach ($stmt->getBindings() as $key => $value)
				if (strpos($stmtText, $key) !== false)
					$stmtPdo->bindValue($key, $value);
		}
		catch (Exception $e)
		{
			throw new Exception('Problem with ' . $stmt->getAsString() . ' creation (' . $e->getMessage() . ')');
		}
		return $stmtPdo;
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
		$stmtPdo = self::convertStatement($stmt);
		try
		{
			$stmtPdo->execute();
		}
		catch (Exception $e)
		{
			throw new Exception('Problem with ' . $stmt->getAsString() . ' execution (' . $e->getMessage() . ')');
		}
		self::$queries []= $stmt;
		return $stmtPdo;
	}

	public static function fetchOne(SqlStatement $stmt)
	{
		$stmtPdo = self::exec($stmt);
		return $stmtPdo->fetch();
	}

	public static function fetchAll(SqlStatement $stmt)
	{
		$stmtPdo = self::exec($stmt);
		return $stmtPdo->fetchAll();
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
