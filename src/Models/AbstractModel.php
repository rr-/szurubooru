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

	public static function getEntities($query, $perPage = null, $page = 1)
	{
		$table = static::getTableName();
		$rows = self::getEntitiesRows($query, $perPage, $page);
		$entities = R::convertToBeans($table, $rows);
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
		return intval($dbQuery->get('row')['count']);
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

	/* FUSE methods for RedBeanPHP, preventing some aliasing errors */
	public function open()
	{
		$this->preload();
	}

	public function after_update()
	{
		$this->preload();
	}

	public function preload()
	{
	}
}
