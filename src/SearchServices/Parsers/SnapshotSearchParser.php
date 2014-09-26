<?php
namespace Szurubooru\SearchServices\Parsers;

class SnapshotSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\Filters\SnapshotFilter;
	}

	protected function decorateFilterFromToken($filter, $token)
	{
		if (substr_count($token->getValue(), ',') !== 1)
			throw new \BadMethodCallException('Not supported');

		if ($token->isNegated())
			throw new \BadMethodCallException('Not supported');

		list ($type, $primaryKey) = explode(',', $token->getValue());

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
		$requirement->setValue($primaryKey);
		$filter->addRequirement($requirement);

		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\SnapshotFilter::REQUIREMENT_TYPE);
		$requirement->setValue(\Szurubooru\Helpers\EnumHelper::snapshotTypeFromString($type));
		$filter->addRequirement($requirement);
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
