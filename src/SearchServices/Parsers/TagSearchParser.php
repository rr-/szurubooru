<?php
namespace Szurubooru\SearchServices\Parsers;

class TagSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\Filters\TagFilter;
	}

	protected function decorateFilterFromToken($filter, $token)
	{
		throw new \Szurubooru\NotSupportedException();
	}

	protected function decorateFilterFromNamedToken($filter, $namedToken)
	{
		throw new \Szurubooru\NotSupportedException();
	}

	protected function getOrderColumn($token)
	{
		if ($token === 'id')
			return \Szurubooru\SearchServices\Filters\TagFilter::ORDER_ID;

		elseif ($token === 'name')
			return \Szurubooru\SearchServices\Filters\TagFilter::ORDER_NAME;

		elseif ($token === 'creation_time')
			return \Szurubooru\SearchServices\Filters\TagFilter::ORDER_CREATION_TIME;

		elseif ($token === 'usage_count')
			return \Szurubooru\SearchServices\Filters\TagFilter::ORDER_USAGE_COUNT;

		throw new \Szurubooru\NotSupportedException();
	}
}
