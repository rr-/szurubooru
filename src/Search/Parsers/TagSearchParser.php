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

    protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $token)
    {
        if ($this->matches($token->getKey(), ['creation_time', 'creation_date', 'date']))
        {
            $this->addRequirementFromDateRangeToken(
                $filter, $token, TagFilter::REQUIREMENT_CREATION_TIME);
            return;
        }

        if ($this->matches($token->getKey(), ['edit_time', 'edit_date']))
        {
            $this->addRequirementFromDateRangeToken(
                $filter, $token, TagFilter::REQUIREMENT_LAST_EDIT_TIME);
            return;
        }

        if ($this->matches($token->getKey(), ['usage_count', 'usages', 'usage']))
        {
            $this->addRequirementFromToken(
                $filter,
                $token,
                TagFilter::REQUIREMENT_USAGE_COUNT,
                self::ALLOW_RANGES | self::ALLOW_COMPOSITE);
            return;
        }

        if ($this->matches($token->getKey(), ['category']))
        {
            $this->addRequirementFromToken(
                $filter,
                $token,
                TagFilter::REQUIREMENT_CATEGORY,
                self::ALLOW_COMPOSITE);
            return;
        }

        throw new NotSupportedException('Unknown token: ' . $token->getKey());
    }

    protected function getOrderColumnMap()
    {
        return
        [
            [['id'],                                     TagFilter::ORDER_ID],
            [['name'],                                   TagFilter::ORDER_NAME],
            [['creation_time', 'creation_date', 'date'], TagFilter::ORDER_CREATION_TIME],
            [['edit_time', 'edit_date'],                 TagFilter::ORDER_LAST_EDIT_TIME],
            [['usage_count', 'usages'],                  TagFilter::ORDER_USAGE_COUNT],
        ];
    }
}
