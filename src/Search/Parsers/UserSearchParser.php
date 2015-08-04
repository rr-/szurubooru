<?php
namespace Szurubooru\Search\Parsers;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\UserFilter;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class UserSearchParser extends AbstractSearchParser
{
    protected function createFilter()
    {
        return new UserFilter;
    }

    protected function decorateFilterFromToken(IFilter $filter, SearchToken $token)
    {
        throw new NotSupportedException();
    }

    protected function decorateFilterFromNamedToken(IFilter $filter, NamedSearchToken $namedToken)
    {
        throw new NotSupportedException();
    }

    protected function getOrderColumnMap()
    {
        return
        [
            [['name'], UserFilter::ORDER_NAME],
            [['registration_time', 'registration_date'], UserFilter::ORDER_REGISTRATION_TIME],
        ];
    }
}
