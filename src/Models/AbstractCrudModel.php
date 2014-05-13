<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

abstract class AbstractCrudModel implements IModel
{
	private static $keyCache = [];

	public static function spawn()
	{
		$entityClassName = static::getEntityClassName();
		$entity = new $entityClassName(new static);
		$entity->fillNew();
		return $entity;
	}

	public static function spawnFromDatabaseRows($input)
	{
		return array_map([get_called_class(), 'spawnFromDatabaseRow'], $input);
	}

	public static function spawnFromDatabaseRow($row)
	{
		$entityClassName = static::getEntityClassName();
		$entity = new $entityClassName(new static);
		$entity->fillFromDatabase($row);
		return $entity;
	}

	public static function remove($entities)
	{
		if (is_array($entities))
			return static::removeMultiple($entities);
		else
			return static::removeSingle($entities);
	}

	protected static function removeMultiple($entities)
	{
		$cb = [get_called_class(), 'removeSingle'];
		Database::transaction(function() use ($entities, $cb)
		{
			foreach ($entities as $entity)
			{
				$cb($entity);
			}
		});
	}

	protected static function removeSingle($entity)
	{
		throw new BadMethodCallException('Not implemented');
	}

	public static function save($entities)
	{
		if (is_array($entities))
			return static::saveMultiple($entities);
		else
			return static::saveSingle($entities);
	}

	protected static function saveMultiple($entities)
	{
		$cb = [get_called_class(), 'saveSingle'];
		return Database::transaction(function() use ($entities, $cb)
		{
			$ret = [];
			foreach ($entities as $entity)
			{
				$ret []= $cb($entity);
			}
			return $ret;
		});
	}

	protected static function saveSingle($entity)
	{
		throw new BadMethodCallException('Not implemented');
	}


	public static function getById($key)
	{
		$ret = self::tryGetById($key);
		if (!$ret)
			throw new SimpleNotFoundException('Invalid %s ID "%s"', static::getTableName(), $key);
		return $ret;
	}

	public static function tryGetById($key)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable(static::getTableName());
		$stmt->setCriterion(new Sql\EqualsFunctor('id', new Sql\Binding($key)));

		$row = Database::fetchOne($stmt);
		return $row
			? static::spawnFromDatabaseRow($row)
			: null;
	}

	public static function getAllByIds(array $ids)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable(static::getTableName());
		$stmt->setCriterion(Sql\InFunctor::fromArray('id', Sql\Binding::fromArray(array_unique($ids))));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return static::spawnFromDatabaseRows($rows);

		return [];
	}

	public static function getCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable(static::getTableName());
		return (int) Database::fetchOne($stmt)['count'];
	}




	public static function getEntityClassName()
	{
		$modelClassName = get_called_class();
		$entityClassName = str_replace('Model', 'Entity', $modelClassName);
		return $entityClassName;
	}

	public static function forgeId($entity)
	{
		$table = static::getTableName();
		if (!Database::inTransaction())
			throw new Exception('Can be run only within transaction');
		if (!$entity->getId())
		{
			$stmt = new Sql\InsertStatement();
			$stmt->setTable($table);
			foreach ($entity as $key => $val)
			{
				$key = TextCaseConverter::convert($key,
					TextCaseConverter::LOWER_CAMEL_CASE,
					TextCaseConverter::SNAKE_CASE);

				$stmt->setColumn($key, new Sql\Binding($val));
			}
			Database::exec($stmt);
			$entity->setId((int) Database::lastInsertId());
		}
	}

	public static function preloadOneToMany($entities,
		$foreignEntityLocalSelector,
		$foreignEntityForeignSelector,
		$foreignEntityProcessor,
		$foreignEntitySetter)
	{
		if (empty($entities))
			return;

		$foreignIds = [];
		$entityMap = [];
		foreach ($entities as $entity)
		{
			$foreignId = $foreignEntityLocalSelector($entity);
			if (!isset($entityMap[$foreignId]))
				$entityMap[$foreignId] = [];
			$entityMap[$foreignId] []= $entity;
			$foreignIds []= $foreignId;
		}

		$foreignEntities = $foreignEntityProcessor($foreignIds);

		foreach ($foreignEntities as $foreignEntity)
		{
			$key = $foreignEntityForeignSelector($foreignEntity);
			foreach ($entityMap[$key] as $entity)
				$foreignEntitySetter($entity, $foreignEntity);
		}
	}
}
