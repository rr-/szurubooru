<?php
namespace Szurubooru\SearchServices\Parsers;

abstract class AbstractSearchParser
{
	public function createFilterFromInputReader(\Szurubooru\Helpers\InputReader $inputReader)
	{
		$filter = $this->createFilter();
		$filter->setOrder(array_merge($this->getOrder($inputReader->order), $filter->getOrder()));

		if ($inputReader->page)
		{
			$filter->setPageNumber($inputReader->page);
			$filter->setPageSize(25);
		}

		$tokens = $this->tokenize($inputReader->query);

		foreach ($tokens as $token)
		{
			if ($token instanceof \Szurubooru\SearchServices\NamedSearchToken)
				$this->decorateFilterFromNamedToken($filter, $token);
			elseif ($token instanceof \Szurubooru\SearchServices\SearchToken)
				$this->decorateFilterFromToken($filter, $token);
			else
				throw new \RuntimeException('Invalid search token type: ' . get_class($token));
		}

		return $filter;
	}

	protected abstract function createFilter();

	protected abstract function decorateFilterFromToken($filter, $token);

	protected abstract function decorateFilterFromNamedToken($filter, $namedToken);

	protected abstract function getOrderColumn($token);

	private function getOrder($query)
	{
		$order = [];
		$tokens = array_filter(preg_split('/\s+/', trim($query)));

		foreach ($tokens as $token)
		{
			$token = preg_split('/,|\s+/', $token);
			$orderToken = $token[0];
			$orderDir = (count($token) === 2 and $token[1] === 'desc')
				? \Szurubooru\SearchServices\Filters\IFilter::ORDER_DESC
				: \Szurubooru\SearchServices\Filters\IFilter::ORDER_ASC;

			$orderColumn = $this->getOrderColumn($orderToken);
			if ($orderColumn === null)
				throw new \InvalidArgumentException('Invalid search order token: ' . $orderToken);

			$order[$orderColumn] = $orderDir;
		}

		return $order;
	}

	private function tokenize($query)
	{
		$searchTokens = [];

		foreach (array_filter(preg_split('/\s+/', trim($query))) as $tokenText)
		{
			$negated = false;
			if (substr($tokenText, 0, 1) === '-')
			{
				$negated = true;
				$tokenText = substr($tokenText, 1);
			}

			if (strpos($tokenText, ':') !== false)
			{
				$searchToken = new \Szurubooru\SearchServices\NamedSearchToken();
				list ($tokenKey, $tokenValue) = explode(':', $tokenText, 1);
				$searchToken->setKey($tokenKey);
				$searchToken->setValue($tokenValue);
			}
			else
			{
				$searchToken = new \Szurubooru\SearchServices\SearchToken();
				$searchToken->setValue($tokenText);
			}

			$searchToken->setNegated($negated);
			$searchTokens[] = $searchToken;
		}

		return $searchTokens;
	}
}
