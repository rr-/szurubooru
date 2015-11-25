<?php
namespace Szurubooru\Search\ParserConfigs;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\TagFilter;
use Szurubooru\Search\ParserConfigs\AbstractSearchParserConfig;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Requirements\RequirementSingleValue;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class TagSearchParserConfig extends AbstractSearchParserConfig
{
    public function __construct()
    {
        $this->defineOrder(TagFilter::ORDER_ID, ['id']);
        $this->defineOrder(TagFilter::ORDER_NAME, ['name']);
        $this->defineOrder(TagFilter::ORDER_CREATION_TIME, ['creation_time']);
        $this->defineOrder(TagFilter::ORDER_LAST_EDIT_TIME, ['edit_time']);
        $this->defineOrder(TagFilter::ORDER_USAGE_COUNT, ['usage_count']);

        $this->defineBasicTokenParser(
            function(SearchToken $token)
            {
                $requirement = new Requirement();
                $requirement->setType(TagFilter::REQUIREMENT_PARTIAL_TAG_NAME);
                $requirement->setValue(new RequirementSingleValue($token->getValue()));
                $requirement->setNegated($token->isNegated());
                return $requirement;
            });

        $this->defineNamedTokenParser(
            TagFilter::REQUIREMENT_CREATION_TIME,
            ['creation_time', 'creation_date', 'date', 'time'],
            function ($value)
            {
                return self::createDateRequirementValue($value);
            });

        $this->defineNamedTokenParser(
            TagFilter::REQUIREMENT_LAST_EDIT_TIME,
            ['edit_time', 'edit_date'],
            function ($value)
            {
                return self::createDateRequirementValue($value);
            });

        $this->defineNamedTokenParser(
            TagFilter::REQUIREMENT_USAGE_COUNT,
            ['usage_count', 'usages', 'usage'],
            self::ALLOW_RANGE | self::ALLOW_COMPOSITE);

        $this->defineNamedTokenParser(
            TagFilter::REQUIREMENT_CATEGORY,
            ['category'],
            self::ALLOW_COMPOSITE);
    }

    public function createFilter()
    {
        return new TagFilter;
    }
}
