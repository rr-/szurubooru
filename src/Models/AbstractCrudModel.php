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
		$query = (new SqlQuery)
			->select('*')
			->from(static::getTableName())
			->where('id = ?')->put($key);

		$row = Database::fetchOne($query);
		if ($row)
			return self::convertRow($row);

		if ($throw)
			throw new SimpleException('Invalid ' . static::getTableName() . ' ID "' . $key . '"');
		return null;
	}

	public static function findByIds(array $ids)
	{
		$query = (new SqlQuery)
			->select('*')
			->from(static::getTableName())
			->where('id')->in()->genSlots($ids)->put($ids);

		$rows = Database::fetchAll($query);
		if ($rows)
			return self::convertRows($rows);

		return [];
	}

	public static function getCount()
	{
		$query = new SqlQuery();
		$query->select('count(1)')->as('count')->from(static::getTableName());
		return Database::fetchOne($query)['count'];
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
			$config = \Chibi\Registry::getConfig();
			$query = (new SqlQuery);
			if ($config->main->dbDriver == 'sqlite')
				$query->insertInto($table)->defaultValues();
			else
				$query->insertInto($table)->values()->open()->close();
			Database::query($query);
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
