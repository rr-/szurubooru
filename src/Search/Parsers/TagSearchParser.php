<?php
namespace Szurubooru\Search\Parsers;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\TagFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class TagSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new TagFilter;
	}

	protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
	{
		$requirement = new Requirement();
		$requirement->setType(TagFilter::REQUIREMENT_PARTIAL_TAG_NAME);
		$requirement->setValue(new RequirementSingleValue($token->getValue()));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken)
	{
		if ($this->matches($namedToken->getKey(), ['category']))
		{
			return $this->addRequirementFromToken(
				$filter,
				$namedToken,
				TagFilter::REQUIREMENT_CATEGORY,
				self::ALLOW_COMPOSITE);
		}

		throw new NotSupportedException();
	}

	protected function getOrderColumn($tokenText)
	{
		if ($this->matches($tokenText, ['id']))
			return TagFilter::ORDER_ID;

		if ($this->matches($tokenText, ['name']))
			return TagFilter::ORDER_NAME;

		if ($this->matches($tokenText, ['creation_time', 'creation_date']))
			return TagFilter::ORDER_CREATION_TIME;

		if ($this->matches($tokenText, ['usage_count', 'usages']))
			return TagFilter::ORDER_USAGE_COUNT;

		throw new NotSupportedException();
	}
}
