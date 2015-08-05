<?php
namespace Szurubooru\Search\Parsers;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\SnapshotFilter;
use Szurubooru\Search\Requirements\Requirement;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class SnapshotSearchParser extends AbstractSearchParser
{
    protected function createFilter()
    {
        return new SnapshotFilter;
    }

    protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
    {
        if (substr_count($token->getValue(), ',') !== 1)
            throw new NotSupportedException('Expected token in form of "type,id"');

        if ($token->isNegated())
            throw new NotSupportedException('Negative searches are not supported in this context');

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
        throw new NotSupportedException('Named tokens are not supported in this context');
    }

    protected function getOrderColumnMap()
    {
        throw new NotSupportedException('Search order is not supported in this context');
    }
}
