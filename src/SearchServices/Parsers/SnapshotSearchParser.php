<?php
namespace Szurubooru\SearchServices\Parsers;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\NotSupportedException;
use Szurubooru\SearchServices\Filters\IFilter;
use Szurubooru\SearchServices\Filters\SnapshotFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Tokens\NamedSearchToken;
use Szurubooru\SearchServices\Tokens\SearchToken;

class SnapshotSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new SnapshotFilter;
	}

	protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
	{
		if (substr_count($token->getValue(), ',') !== 1)
			throw new NotSupportedException();

		if ($token->isNegated())
			throw new NotSupportedException();

		list ($type, $primaryKey) = explode(',', $token->getValue());

		$requirement = new Requirement();
		$requirement->setType(SnapshotFilter::REQUIREMENT_PRIMARY_KEY);
		$requirement->setValue($this->createRequirementValue($primaryKey));
		$filter->addRequirement($requirement);

		$requirement = new Requirement();
		$requirement->setType(SnapshotFilter::REQUIREMENT_TYPE);
		$requirement->setValue($this->createRequirementValue(EnumHelper::snapshotTypeFromString($type)));
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken)
	{
		throw new NotSupportedException();
	}

	protected function getOrderColumn($tokenText)
	{
		throw new NotSupportedException();
	}
}
