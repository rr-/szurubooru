<?php
abstract class AbstractSearchService
{
	protected static function getModelClassName()
	{
		$searchServiceClassName = get_called_class();
		$modelClassName = str_replace('SearchService', 'Model', $searchServiceClassName);
		return $modelClassName;
	}

	protected static function decorate(SqlSelectStatement $stmt, $searchQuery)
	{
		throw new NotImplementedException();
	}

	protected static function decoratePager(SqlSelectStatement $stmt, $perPage, $page)
	{
		if ($perPage === null)
			return;
		$stmt->setLimit(
			new SqlBinding($perPage),
			new SqlBinding(($page - 1) * $perPage));
	}

	public static function getEntitiesRows($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$stmt = new SqlSelectStatement();
		$stmt->setColumn($table . '.*');
		static::decorate($stmt, $searchQuery);
		static::decoratePager($stmt, $perPage, $page);

		$rows = Database::fetchAll($stmt);
		return $rows;
	}

	public static function getEntities($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$rows = static::getEntitiesRows($searchQuery, $perPage, $page);
		return $modelClassName::convertRows($rows);
	}

	public static function getEntityCount($searchQuery)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$innerStmt = new SqlSelectStatement();
		static::decorate($innerStmt, $searchQuery);

		$stmt = new SqlSelectStatement();
		$stmt->setColumn(new SqlAliasOperator(new SqlCountOperator('1'), 'count'));
		$stmt->setSource($innerStmt);

		return Database::fetchOne($stmt)['count'];
	}
}
