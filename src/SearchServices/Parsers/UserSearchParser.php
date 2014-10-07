<?php
namespace Szurubooru\SearchServices\Parsers;

class UserSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\Filters\UserFilter;
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
		if ($token === 'name')
			return \Szurubooru\SearchServices\Filters\UserFilter::ORDER_NAME;

		elseif ($token === 'registration_time')
			return \Szurubooru\SearchServices\Filters\UserFilter::ORDER_REGISTRATION_TIME;

		else
			throw new \Szurubooru\NotSupportedException();
	}
}
