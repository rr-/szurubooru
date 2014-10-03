<?php
namespace Szurubooru\SearchServices\Parsers;

abstract class AbstractSearchParser
{
	const ALLOW_COMPOSITE = 1;
	const ALLOW_RANGES = 2;

	public function createFilterFromInputReader(\Szurubooru\Helpers\InputReader $inputReader)
	{
		$filter = $this->createFilter();
		$filter->setOrder($this->getOrder($inputReader->order) + $filter->getOrder());

		if ($inputReader->page)
		{
			$filter->setPageNumber($inputReader->page);
			$filter->setPageSize(25);
		}

		$tokens = $this->tokenize($inputReader->query);

		foreach ($tokens as $token)
		{
			if ($token instanceof \Szurubooru\SearchServices\Tokens\NamedSearchToken)
			{
				if ($token->getKey() === 'order')
					$filter->setOrder($this->getOrder($token->getValue()) + $filter->getOrder());
				else
					$this->decorateFilterFromNamedToken($filter, $token);
			}
			elseif ($token instanceof \Szurubooru\SearchServices\Tokens\SearchToken)
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

	protected function createRequirementValue($text, $flags = 0, $valueDecorator = null)
	{
		if ($valueDecorator === null)
		{
			$valueDecorator = function($value)
			{
				return $value;
			};
		}

		if ((($flags & self::ALLOW_RANGES) === self::ALLOW_RANGES) and substr_count($text, '..') === 1)
		{
			list ($minValue, $maxValue) = explode('..', $text);
			$minValue = $valueDecorator($minValue);
			$maxValue = $valueDecorator($maxValue);
			$tokenValue = new \Szurubooru\SearchServices\Requirements\RequirementRangedValue();
			$tokenValue->setMinValue($minValue);
			$tokenValue->setMaxValue($maxValue);
			return $tokenValue;
		}
		else if ((($flags & self::ALLOW_COMPOSITE) === self::ALLOW_COMPOSITE) and strpos($text, ',') !== false)
		{
			$values = explode(',', $text);
			$values = array_map($valueDecorator, $values);
			$tokenValue = new \Szurubooru\SearchServices\Requirements\RequirementCompositeValue();
			$tokenValue->setValues($values);
			return $tokenValue;
		}

		$value = $valueDecorator($text);
		return new \Szurubooru\SearchServices\Requirements\RequirementSingleValue($value);
	}

	protected function addRequirementFromToken($filter, $token, $type, $flags, $valueDecorator = null)
	{
		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType($type);
		$requirement->setValue($this->createRequirementValue($token->getValue(), $flags, $valueDecorator));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	private function getOrder($query)
	{
		$order = [];
		$tokens = array_filter(preg_split('/\s+/', trim($query)));

		foreach ($tokens as $token)
		{
			$token = preg_split('/,|\s+/', $token);
			$orderToken = $token[0];

			if (count($token) === 1)
			{
				$orderDir = \Szurubooru\SearchServices\Filters\IFilter::ORDER_DESC;
			}
			elseif (count($token) === 2)
			{
				if ($token[1] === 'desc')
					$orderDir = \Szurubooru\SearchServices\Filters\IFilter::ORDER_DESC;
				elseif ($token[1] === 'asc')
					$orderDir = \Szurubooru\SearchServices\Filters\IFilter::ORDER_ASC;
				else
					throw new \Exception('Wrong search order direction');
			}
			else
				throw new \Exception('Wrong search order token');

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
				$searchToken = new \Szurubooru\SearchServices\Tokens\NamedSearchToken();
				list ($tokenKey, $tokenValue) = explode(':', $tokenText, 2);
				$searchToken->setKey($tokenKey);
				$searchToken->setValue($tokenValue);
			}
			else
			{
				$searchToken = new \Szurubooru\SearchServices\Tokens\SearchToken();
				$searchToken->setValue($tokenText);
			}

			$searchToken->setNegated($negated);
			$searchTokens[] = $searchToken;
		}

		return $searchTokens;
	}
}
