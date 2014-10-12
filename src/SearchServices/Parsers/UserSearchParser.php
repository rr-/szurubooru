<?php
namespace Szurubooru\SearchServices\Parsers;
use Szurubooru\NotSupportedException;
use Szurubooru\SearchServices\Filters\IFilter;
use Szurubooru\SearchServices\Filters\UserFilter;
use Szurubooru\SearchServices\Tokens\NamedSearchToken;
use Szurubooru\SearchServices\Tokens\SearchToken;

class UserSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new UserFilter;
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
		if ($tokenText === 'name')
			return UserFilter::ORDER_NAME;

		elseif ($tokenText === 'registration_time' or $tokenText === 'registration_date')
			return UserFilter::ORDER_REGISTRATION_TIME;

		else
			throw new NotSupportedException();
	}
}
