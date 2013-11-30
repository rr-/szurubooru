<?php
abstract class AbstractModel extends RedBean_SimpleModel
{
	public static function getTableName()
	{
		throw new SimpleException('Not implemented.');
	}

	public static function getQueryBuilder()
	{
		throw new SimpleException('Not implemented.');
	}

	public static function getEntitiesRows($query, $perPage = null, $page = 1)
	{
		$table = static::getTableName();
		$dbQuery = R::$f->getNew()->begin();
		$dbQuery->select($table . '.*');
		$builder = static::getQueryBuilder();
		if ($builder)
			$builder::build($dbQuery, $query);
		else
			$dbQuery->from($table);
		if ($perPage !== null)
		{
			$dbQuery->limit('?')->put($perPage);
			$dbQuery->offset('?')->put(($page - 1) * $perPage);
		}

		$rows = $dbQuery->get();
		return $rows;
	}

	protected static function convertRows($rows, $table, $fast = false)
	{
		if (!$fast)
			return R::convertToBeans($table, $rows);

		$entities = R::dispense($table, count($rows));
		reset($entities);
		foreach ($rows as $row)
		{
			$entity = current($entities);
			$entity->import($row);
			next($entities);
		}
		reset($entities);
		return $entities;
	}

	public static function getEntities($query, $perPage = null, $page = 1, $fast = false)
	{
		$table = static::getTableName();
		$rows = self::getEntitiesRows($query, $perPage, $page);
		$entities = self::convertRows($rows, $table, $fast);
		return $entities;
	}

	public static function getEntityCount($query)
	{
		$table = static::getTableName();
		$dbQuery = R::$f->getNew()->begin();
		$dbQuery->select('COUNT(1)')->as('count');
		$builder = static::getQueryBuilder();
		if ($builder)
			$builder::build($dbQuery, $query);
		else
			$dbQuery->from($table);
		$ret = intval($dbQuery->get('row')['count']);
		return $ret;
	}

	public static function getEntitiesWithCount($query, $perPage = null, $page = 1)
	{
		$entities = self::getEntities($query, $perPage, $page, true);
		$count = self::getEntityCount($query);
		return [$entities, $count];
	}

	public static function create()
	{
		return R::dispense(static::getTableName());
	}

	public static function remove($entity)
	{
		R::trash($entity);
	}

	public static function save($entity)
	{
		R::store($entity);
	}
}
