<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

abstract class AbstractCrudModel implements IModel
{
	private static $keyCache = [];

	public static function spawn()
	{
		$entityClassName = static::getEntityClassName();
		return new $entityClassName();
	}

	public static function remove($entities)
	{
		throw new NotImplementedException();
	}

	public static function save($entity)
	{
		throw new NotImplementedException();
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
			? static::convertRow($row)
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
			return static::convertRows($rows);

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

	public static function convertRow($row)
	{
		$entity = static::spawn();

		//todo: force this to be implemented by children
		//instead of providing clumsy generic solution

		if (isset($row['id']))
			$row['id'] = (int) $row['id'];

		foreach ($row as $key => $val)
		{
			if (isset(self::$keyCache[$key]))
			{
				$key = self::$keyCache[$key];
			}
			else
			{
				$key = self::$keyCache[$key] = TextCaseConverter::convert($key,
					TextCaseConverter::SNAKE_CASE,
					TextCaseConverter::LOWER_CAMEL_CASE);
			}

			if (property_exists($entity, $key))
			{
				$reflectionProperty = new ReflectionProperty(get_class($entity), $key);
				$reflectionProperty->setAccessible(true);
				$reflectionProperty->setValue($entity, $val);
			}
			else
			{
				$entity->$key = $val;
			}
		}
		return $entity;
	}

	public static function convertRows(array $rows)
	{
		$entities = [];
		foreach ($rows as $i => $row)
		{
			$entities[$i] = static::convertRow($row);
		}
		return $entities;
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
