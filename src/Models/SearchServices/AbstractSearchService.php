<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

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

	protected static function decorateParser(Sql\SelectStatement $stmt, $searchQuery)
	{
		$parserClassName = self::getParserClassName();
		(new $parserClassName)->decorate($stmt, $searchQuery);
	}

	protected static function decorateCustom(Sql\SelectStatement $stmt)
	{
	}

	protected static function decoratePager(Sql\SelectStatement $stmt, $perPage, $page)
	{
		if ($perPage === null)
			return;
		$stmt->setLimit(
			new Sql\Binding($perPage),
			new Sql\Binding(($page - 1) * $perPage));
	}

	public static function getEntitiesRows($searchQuery, $perPage = null, $page = 1)
	{
		$modelClassName = self::getModelClassName();
		$table = $modelClassName::getTableName();

		$stmt = new Sql\SelectStatement();
		$stmt->setColumn($table . '.*');
		$stmt->setTable($table);
		static::decorateParser($stmt, $searchQuery);
		static::decorateCustom($stmt);
		static::decoratePager($stmt, $perPage, $page);

		return Database::fetchAll($stmt);
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

		$innerStmt = new Sql\SelectStatement();
		$innerStmt->setTable($table);
		static::decorateParser($innerStmt, $searchQuery);
		static::decorateCustom($innerStmt);
		$innerStmt->resetOrderBy();

		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setSource($innerStmt, 'inner_stmt');

		return Database::fetchOne($stmt)['count'];
	}
}
