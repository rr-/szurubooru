<?php
namespace Szurubooru\SearchServices\Parsers;
use Szurubooru\NotSupportedException;
use Szurubooru\SearchServices\Filters\IFilter;
use Szurubooru\SearchServices\Filters\TagFilter;
use Szurubooru\SearchServices\Requirements\Requirement;
use Szurubooru\SearchServices\Requirements\RequirementSingleValue;
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
		$requirement = new Requirement();
		$requirement->setType(TagFilter::REQUIREMENT_PARTIAL_TAG_NAME);
		$requirement->setValue(new RequirementSingleValue($token->getValue()));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken)
	{
		if ($namedToken->getKey() === 'category')
		{
			$this->addRequirementFromToken(
				$filter,
				$namedToken,
				TagFilter::REQUIREMENT_CATEGORY,
				self::ALLOW_COMPOSITE);
		}

		else
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
