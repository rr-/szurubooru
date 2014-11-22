<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\IEntityConverter;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementCompositeValue;
use Szurubooru\Search\Requirements\RequirementRangedValue;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Result;

abstract class AbstractDao implements ICrudDao, IBatchDao
{
	protected $pdo;
	protected $tableName;
	protected $entityConverter;
	protected $driver;

	public function __construct(
		DatabaseConnection $databaseConnection,
		$tableName,
		IEntityConverter $entityConverter)
	{
		$this->setDatabaseConnection($databaseConnection);
		$this->tableName = $tableName;
		$this->entityConverter = $entityConverter;
		$this->entityConverter->setEntityDecorator(function($entity)
			{
				$this->afterLoad($entity);
			});
	}

	public function getTableName()
	{
		return $this->tableName;
	}

	public function getEntityConverter()
	{
		return $this->entityConverter;
	}

	public function save(&$entity)
	{
		$entity = $this->upsert($entity);
		$this->afterSave($entity);
		$this->afterBatchSave([$entity]);
		return $entity;
	}

	public function batchSave(array $entities)
	{
		foreach ($entities as $key => $entity)
		{
			$entities[$key] = $this->upsert($entity);
			$this->afterSave($entity);
		}
		if (count($entities) > 0)
			$this->afterBatchSave([$entity]);
		return $entities;
	}

	public function findAll()
	{
		$query = $this->pdo->from($this->tableName);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}

	public function findById($entityId)
	{
		return $this->findOneBy($this->getIdColumn(), $entityId);
	}

	public function findByIds($entityIds)
	{
		return $this->findBy($this->getIdColumn(), $entityIds);
	}

	public function findFiltered(IFilter $searchFilter)
	{
		$query = $this->pdo->from($this->tableName);

		$orderByString = self::compileOrderBy($searchFilter->getOrder());
		if ($orderByString)
			$query->orderBy($orderByString);

		$this->decorateQueryFromFilter($query, $searchFilter);
		if ($searchFilter->getPageSize() > 0)
		{
			$query->limit($searchFilter->getPageSize());
			$query->offset($searchFilter->getPageSize() * max(0, $searchFilter->getPageNumber() - 1));
		}
		$entities = $this->arrayToEntities(iterator_to_array($query));

		$query = $this->pdo->from($this->tableName);
		$this->decorateQueryFromFilter($query, $searchFilter);
		$totalRecords = count($query);

		$searchResult = new Result();
		$searchResult->setSearchFilter($searchFilter);
		$searchResult->setEntities($entities);
		$searchResult->setTotalRecords($totalRecords);
		$searchResult->setPageNumber($searchFilter->getPageNumber());
		$searchResult->setPageSize($searchFilter->getPageSize());
		return $searchResult;
	}

	public function deleteAll()
	{
		foreach ($this->findAll() as $entity)
		{
			$this->beforeDelete($entity);
		}
		$this->pdo->deleteFrom($this->tableName)->execute();
	}

	public function deleteById($entityId)
	{
		return $this->deleteBy($this->getIdColumn(), $entityId);
	}

	public function update(Entity $entity)
	{
		$arrayEntity = $this->entityConverter->toArray($entity);
		unset($arrayEntity['id']);
		$this->pdo->update($this->tableName)->set($arrayEntity)->where($this->getIdColumn(), $entity->getId())->execute();
		return $entity;
	}

	public function create(Entity $entity)
	{
		$sql = 'UPDATE sequencer SET lastUsedId = (@lastUsedId := (lastUsedId + 1)) WHERE tableName = :tableName';
		$query = $this->pdo->prepare($sql);
		$query->bindValue(':tableName', $this->tableName);
		$query->execute();
		$lastUsedId = $this->pdo->query('SELECT @lastUsedId')->fetchColumn();

		$entity->setId(intval($lastUsedId));
		$arrayEntity = $this->entityConverter->toArray($entity);
		$this->pdo->insertInto($this->tableName)->values($arrayEntity)->execute();
		return $entity;
	}

	protected function getIdColumn()
	{
		return 'id';
	}

	protected function hasAnyRecords()
	{
		return count(iterator_to_array($this->pdo->from($this->tableName)->limit(1))) > 0;
	}

	protected function findBy($columnName, $value)
	{
		if (is_array($value) && empty($value))
			return [];
		$query = $this->pdo->from($this->tableName)->where($columnName, $value);
		$arrayEntities = iterator_to_array($query);
		return $this->arrayToEntities($arrayEntities);
	}

	protected function findOneBy($columnName, $value)
	{
		$entities = $this->findBy($columnName, $value);
		if (!$entities)
			return null;
		return array_shift($entities);
	}

	protected function deleteBy($columnName, $value)
	{
		foreach ($this->findBy($columnName, $value) as $entity)
		{
			$this->beforeDelete($entity);
		}
		$this->pdo->deleteFrom($this->tableName)->where($columnName, $value)->execute();
	}

	protected function afterLoad(Entity $entity)
	{
	}

	protected function afterSave(Entity $entity)
	{
	}

	protected function afterBatchSave(array $entities)
	{
	}

	protected function beforeDelete(Entity $entity)
	{
	}

	protected function decorateQueryFromRequirement($query, Requirement $requirement)
	{
		$value = $requirement->getValue();
		$sqlColumn = $requirement->getType();

		if ($value instanceof RequirementCompositeValue)
		{
			$sql = $sqlColumn;
			$bindings = $value->getValues();

			if ($requirement->isNegated())
				$sql = 'NOT ' . $sql;
		}

		else if ($value instanceof RequirementRangedValue)
		{
			if ($value->getMinValue() && $value->getMaxValue())
			{
				$sql = $sqlColumn . ' >= ? AND ' . $sqlColumn . ' <= ?';
				$bindings = [$value->getMinValue(), $value->getMaxValue()];
			}
			elseif ($value->getMinValue())
			{
				$sql = $sqlColumn . ' >= ?';
				$bindings = [$value->getMinValue()];
			}
			elseif ($value->getMaxValue())
			{
				$sql = $sqlColumn . ' <= ?';
				$bindings = [$value->getMaxValue()];
			}
			else
				throw new \RuntimeException('Neither min or max value was supplied');

			if ($requirement->isNegated())
				$sql = 'NOT (' . $sql . ')';
		}

		else if ($value instanceof RequirementSingleValue)
		{
			$sql = $sqlColumn;
			$bindings = [$value->getValue()];

			if ($requirement->isNegated())
				$sql = 'NOT ' . $sql;
		}

		else
			throw new \Exception('Bad value: ' . get_class($value));

		$query->where($sql, $bindings);
	}

	protected function arrayToEntities(array $arrayEntities, $entityConverter = null)
	{
		if ($entityConverter === null)
			$entityConverter = $this->entityConverter;

		$entities = [];
		foreach ($arrayEntities as $arrayEntity)
		{
			$entity = $entityConverter->toEntity($arrayEntity);
			$entities[$entity->getId()] = $entity;
		}
		return $entities;
	}

	private function setDatabaseConnection(DatabaseConnection $databaseConnection)
	{
		$this->pdo = $databaseConnection->getPDO();
		$this->driver = $databaseConnection->getDriver();
	}

	private function decorateQueryFromFilter($query, IFilter $filter)
	{
		foreach ($filter->getRequirements() as $requirement)
		{
			$this->decorateQueryFromRequirement($query, $requirement);
		}
	}

	private function compileOrderBy($order)
	{
		$orderByString = '';
		foreach ($order as $orderColumn => $orderDir)
		{
			if ($orderColumn === IFilter::ORDER_RANDOM)
			{
				$driver = $this->driver;
				if ($driver === 'sqlite')
				{
					$orderColumn = 'RANDOM()';
				}
				else
				{
					$orderColumn = 'RAND()';
				}
			}
			$orderByString .= $orderColumn . ' ' . ($orderDir === IFilter::ORDER_DESC ? 'DESC' : 'ASC') . ', ';
		}
		return substr($orderByString, 0, -2);
	}

	private function upsert(Entity $entity)
	{
		if ($entity->getId())
		{
			return $this->update($entity);
		}
		else
		{
			return $this->create($entity);
		}
	}
}
