<?php
use \Chibi\Sql as Sql;

abstract class AbstractSearchService
{
	protected static function getModelClassName()
	{
		$searchServiceClassName = get_called_class();
		$modelClassName = str_replace('SearchService', 'Model', $searchServiceClassName);
		return $modelClassName;
	}

	protected static function getParserClassName()
	{
		$searchServiceClassName = get_called_class();
		$parserClassName = str_replace('SearchService', 'SearchParser', $searchServiceClassName);
		return $parserClassName;
	}

	protected static function decorateParser($stmt, $searchQuery)
	{
		$parserClassName = self::getParserClassName();
		(new $parserClassName)->decorate($stmt, $searchQuery);
	}

	protected static function decorateCustom($stmt)
	{
	}

	protected static function decoratePager($stmt, $perPage, $page)
	{
		if ($perPage === null)
			return;
		$stmt->setLimit(
			new Sql\Binding($perPage),
			new Sql\Binding(($page - 1) * $perPage));
	}

	public static function getEntities($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$stmt = Sql\Statements::select();
		$stmt->setColumn($table . '.*');
		$stmt->setTable($table);
		static::decorateParser($stmt, $searchQuery);
		static::decorateCustom($stmt);
		static::decoratePager($stmt, $perPage, $page);

		$rows = Core::getDatabase()->fetchAll($stmt);
		$modelClassName = self::getModelClassName();
		return $modelClassName::spawnFromDatabaseRows($rows);
	}

	public static function getEntityCount($searchQuery)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$innerStmt = Sql\Statements::select();
		$innerStmt->setTable($table);
		static::decorateParser($innerStmt, $searchQuery);
		static::decorateCustom($innerStmt);
		$innerStmt->resetOrderBy();

		$stmt = Sql\Statements::select();
		$stmt->setColumn(Sql\Functors::alias(Sql\Functors::count('1'), 'count'));
		$stmt->setSource($innerStmt, 'inner_stmt');

		return Core::getDatabase()->fetchOne($stmt)['count'];
	}
}
