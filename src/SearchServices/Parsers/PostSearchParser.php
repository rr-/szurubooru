<?php
namespace Szurubooru\SearchServices\Parsers;

class PostSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\PostSearchFilter;
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
		throw new \BadMethodCallException('Not supported');
	}
}
