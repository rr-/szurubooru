<?php
namespace Szurubooru\Dao\Services;

abstract class AbstractSearchService
{
	const ORDER_DESC = -1;
	const ORDER_ASC = 1;

	private $tableName;
	private $entityConverter;
	private $fpdo;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\AbstractDao $dao)
	{
		$this->tableName = $dao->getTableName();
		$this->entityConverter = $dao->getEntityConverter();
		$this->fpdo = new \FluentPDO($databaseConnection->getPDO());
	}

	public function getFiltered(
		\Szurubooru\Dao\SearchFilter $searchFilter)
	{
		list ($basicTokens, $complexTokens) = $this->tokenize($searchFilter->query);

		$filter = [];
		if ($basicTokens)
			$this->decorateFilterWithBasicTokens($filter, $basicTokens);
		if ($complexTokens)
			$this->decorateFilterWithComplexTokens($filter, $complexTokens);

		$order = $this->getOrder($searchFilter->order);
		$pageSize = min(100, max(1, $searchFilter->pageSize));
		$pageNumber = max(1, $searchFilter->pageNumber) - 1;

		//todo: clean up
		$orderByString = '';
		foreach ($order as $orderColumn => $orderDir)
		{
			$orderByString .= $orderColumn . ' ' . ($orderDir === self::ORDER_DESC ? 'DESC' : 'ASC') . ', ';
		}
		$orderByString = substr($orderByString, 0, -2);

		$query = $this->fpdo
			->from($this->tableName)
			->orderBy($orderByString)
			->limit($pageSize)
			->offset($pageSize * $pageNumber);

		$entities = [];
		foreach ($query as $arrayEntity)
			$entities[] = $this->entityConverter->toEntity($arrayEntity);

		$query->select('COUNT(1) AS c');
		$totalRecords = intval(iterator_to_array($query)[0]['c']);
		return new \Szurubooru\Dao\SearchResult($searchFilter, $entities, $totalRecords);
	}

	protected function decorateFilterWithBasicTokens($filter, $basicTokens)
	{
		throw new \BadMethodCallException('Not supported');
	}

	protected function decorateFilterWithComplexTokens($filter, $complexTokens)
	{
		throw new \BadMethodCallException('Not supported');
	}

	protected function getOrderColumn($token)
	{
		throw new \BadMethodCallException('Not supported');
	}

	protected function getDefaultOrderColumn()
	{
		return 'id';
	}

	protected function getDefaultOrderDir()
	{
		return self::ORDER_DESC;
	}

	private function getOrder($query)
	{
		$order = [];
		$tokens = array_filter(preg_split('/\s+/', $query));
		foreach ($tokens as $token)
		{
			$token = preg_split('/,|\s+/', $token);
			$orderToken = $token[0];
			$orderDir = (count($token) === 2 and $token[1] === 'desc') ? self::ORDER_DESC : self::ORDER_ASC;

			$orderColumn = $this->getOrderColumn($orderToken);
			if ($orderColumn === null)
				throw new \InvalidArgumentException('Invalid search order token: ' . $orderToken);

			$order[$orderColumn] = $orderDir;
		}
		$defaultOrderColumn = $this->getDefaultOrderColumn();
		$defaultOrderDir = $this->getDefaultOrderDir();
		if ($defaultOrderColumn)
			$order[$defaultOrderColumn] = $defaultOrderDir;
		return $order;
	}

	private function tokenize($query)
	{
		$basicTokens = [];
		$complexTokens = [];

		$tokens = array_filter(preg_split('/\s+/', $query));
		foreach ($tokens as $token)
		{
			if (strpos($token, ':') !== false)
			{
				list ($key, $value) = explode(':', $token, 1);
				$complexTokens[$key] = $value;
			}
			else
			{
				$basicTokens[] = $token;
			}
		}

		return [$basicTokens, $complexTokens];
	}
}
