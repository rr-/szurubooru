<?php
abstract class AbstractSearchService
{
	protected static function getModelClassName()
	{
		$searchServiceClassName = get_called_class();
		$modelClassName = str_replace('SearchService', 'Model', $searchServiceClassName);
		return $modelClassName;
	}

	protected static function decorate(SqlQuery $sqlQuery, $searchQuery)
	{
		throw new NotImplementedException();
	}

	protected static function decoratePager(SqlQuery $sqlQuery, $perPage, $page)
	{
		if ($perPage === null)
			return;
		$sqlQuery->limit('?')->put($perPage);
		$sqlQuery->offset('?')->put(($page - 1) * $perPage);
	}

	static function getEntitiesRows($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$sqlQuery = new SqlQuery();
		$sqlQuery->select($table . '.*');
		static::decorate($sqlQuery, $searchQuery);
		self::decoratePager($sqlQuery, $perPage, $page);

		$rows = Database::fetchAll($sqlQuery);
		return $rows;
	}

	static function getEntities($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$rows = static::getEntitiesRows($searchQuery, $perPage, $page);
		return $modelClassName::convertRows($rows);
	}

	static function getEntityCount($searchQuery)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$sqlQuery = new SqlQuery();
		$sqlQuery->select('count(1)')->as('count');
		static::decorate($sqlQuery, $searchQuery);

		return Database::fetchOne($sqlQuery)['count'];
	}
}
