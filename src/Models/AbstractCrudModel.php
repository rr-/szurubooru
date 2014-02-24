<?php
abstract class AbstractCrudModel implements IModel
{
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



	public static function findById($key, $throw = true)
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable(static::getTableName());
		$stmt->setCriterion(new SqlEqualsOperator('id', new SqlBinding($key)));

		$row = Database::fetchOne($stmt);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleNotFoundException('Invalid ' . static::getTableName() . ' ID "' . $key . '"');
		return null;
	}

	public static function findByIds(array $ids)
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn('*');
		$stmt->setTable(static::getTableName());
		$stmt->setCriterion(SqlInOperator::fromArray('id', SqlBinding::fromArray(array_unique($ids))));

		$rows = Database::fetchAll($stmt);
		if ($rows)
			return self::convertRows($rows);

		return [];
	}

	public static function getCount()
	{
		$stmt = new SqlSelectStatement();
		$stmt->setColumn(new SqlAliasOperator(new SqlCountOperator('1'), 'count'));
		$stmt->setTable(static::getTableName());
		return Database::fetchOne($stmt)['count'];
	}




	public static function getEntityClassName()
	{
		$modelClassName = get_called_class();
		$entityClassName = str_replace('Model', 'Entity', $modelClassName);
		return $entityClassName;
	}

	public static function convertRow($row)
	{
		$entity = self::spawn();
		foreach ($row as $key => $val)
		{
			$key = TextHelper::snakeCaseToCamelCase($key, true);
			$entity->$key = $val;
		}
		return $entity;
	}

	public static function convertRows(array $rows)
	{
		$keyCache = [];
		$entities = [];
		foreach ($rows as $i => $row)
		{
			$entity = self::spawn();
			foreach ($row as $key => $val)
			{
				if (isset($keyCache[$key]))
					$key = $keyCache[$key];
				else
					$key = $keyCache[$key] = TextHelper::snakeCaseToCamelCase($key, true);
				$entity->$key = $val;
			}
			$entities[$i] = $entity;
		}
		return $entities;
	}



	public static function forgeId($entity)
	{
		$table = static::getTableName();
		if (!Database::inTransaction())
			throw new Exception('Can be run only within transaction');
		if (!$entity->id)
		{
			$stmt = new SqlInsertStatement();
			$stmt->setTable($table);
			Database::exec($stmt);
			$entity->id = Database::lastInsertId();
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
