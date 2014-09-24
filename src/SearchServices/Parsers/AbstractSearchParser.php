<?php
namespace Szurubooru\SearchServices\Parsers;

abstract class AbstractSearchParser
{
	public function createFilterFromFormData(\Szurubooru\FormData\SearchFormData $formData)
	{
		$filter = $this->createFilter();
		$filter->setOrder(array_merge($this->getOrder($formData->order), $filter->getOrder()));

		$tokens = $this->tokenize($formData->query);

		foreach ($tokens as $token)
		{
			if ($token instanceof \Szurubooru\SearchServices\NamedSearchToken)
				$this->decorateFilterFromNamedToken($filter, $token);
			elseif ($token instanceof \Szurubooru\SearchService\SearchToken)
				$this->decorateFilterFromToken($filter, $token);
			else
				throw new \RuntimeException('Invalid search token type');
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
		$tokens = array_filter(preg_split('/\s+/', $query));

		foreach ($tokens as $token)
		{
			$token = preg_split('/,|\s+/', $token);
			$orderToken = $token[0];
			$orderDir = (count($token) === 2 and $token[1] === 'desc')
				? \Szurubooru\SearchServices\AbstractSearchFilter::ORDER_DESC
				: \Szurubooru\SearchServices\AbstractSearchFilter::ORDER_ASC;

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

		foreach (array_filter(preg_split('/\s+/', $query)) as $tokenText)
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
