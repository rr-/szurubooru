<?php
namespace Szurubooru\SearchServices\Parsers;
use Szurubooru\NotSupportedException;
use Szurubooru\SearchServices\Filters\IFilter;
use Szurubooru\SearchServices\Filters\TagFilter;
use Szurubooru\SearchServices\Tokens\NamedSearchToken;
use Szurubooru\SearchServices\Tokens\SearchToken;

class TagSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new TagFilter;
	}

	protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
	{
		throw new NotSupportedException();
	}

	protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken)
	{
		throw new NotSupportedException();
	}

	protected function getOrderColumn($tokenText)
	{
		if ($tokenText === 'id')
			return TagFilter::ORDER_ID;

		elseif ($tokenText === 'name')
			return TagFilter::ORDER_NAME;

		elseif ($tokenText === 'creation_time' or $tokenText === 'creation_date')
			return TagFilter::ORDER_CREATION_TIME;

		elseif ($tokenText === 'usage_count')
			return TagFilter::ORDER_USAGE_COUNT;

		else
			throw new NotSupportedException();
	}
}
