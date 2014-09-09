<?php
namespace Szurubooru\Dao\Services;

abstract class AbstractSearchService
{
	const ORDER_DESC = -1;
	const ORDER_ASC = 1;

	private $collection;
	private $entityConverter;

	public function __construct(
		\Szurubooru\Dao\AbstractDao $dao)
	{
		$this->collection = $dao->getCollection();
		$this->entityConverter = $dao->getEntityConverter();
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

		$cursor = $this->collection->find($filter);
		$totalRecords = $cursor->count();
		$cursor->sort($order);
		$cursor->skip($pageSize * $pageNumber);
		$cursor->limit($pageSize);

		$entities = [];
		foreach ($cursor as $arrayEntity)
			$entities[] = $this->entityConverter->toEntity($arrayEntity);

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
		return '_id';
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
			if (count($token) === 2)
			{
				$orderDir = $token[1] === 'desc' ? self::ORDER_DESC : self::ORDER_ASC;
				$orderToken = $token[0];
			}
			else
			{
				$orderDir = self::ORDER_ASC;
				$orderToken = $token;
			}
			$orderColumn = $this->getOrderColumn($token[0]);
			if ($orderColumn === null)
				throw new \InvalidArgumentException('Invalid search order token: ' . $token);
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
