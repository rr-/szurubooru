<?php
namespace Szurubooru\SearchServices\Parsers;

class UserSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\UserSearchFilter;
	}

	protected function decorateFilterFromToken($filter, $token)
	{
		throw new \BadMethodCallException('Not supported');
	}

	protected function decorateFilterFromNamedToken($filter, $namedToken)
	{
		throw new \BadMethodCallException('Not supported');
	}

	protected function getOrderColumn($token)
	{
		if ($token === 'name')
			return 'name';

		if (in_array($token, ['registrationDate', 'registrationTime', 'registered', 'joinDate', 'joinTime', 'joined']))
			return 'registrationTime';

		return null;
	}
}
