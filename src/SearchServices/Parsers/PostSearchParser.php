<?php
namespace Szurubooru\SearchServices\Parsers;

class PostSearchParser extends AbstractSearchParser
{
	protected function createFilter()
	{
		return new \Szurubooru\SearchServices\Filters\PostFilter;
	}

	protected function decorateFilterFromToken($filter, $token)
	{
		$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
		$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_TAG);
		$requirement->setValue($this->createRequirementValue($token->getValue()));
		$requirement->setNegated($token->isNegated());
		$filter->addRequirement($requirement);
	}

	protected function decorateFilterFromNamedToken($filter, $token)
	{
		if ($token->getKey() === 'id')
		{
			$requirement = new \Szurubooru\SearchServices\Requirements\Requirement();
			$requirement->setType(\Szurubooru\SearchServices\Filters\PostFilter::REQUIREMENT_ID);
			$requirement->setValue($this->createRequirementValue($token->getValue(), self::ALLOW_COMPOSITE | self::ALLOW_RANGES));
			$requirement->setNegated($token->isNegated());
			$filter->addRequirement($requirement);
		}
		else
		{
			throw new \BadMethodCallException('Not supported');
		}
	}

	protected function getOrderColumn($token)
	{
		throw new \BadMethodCallException('Not supported');
	}
}
