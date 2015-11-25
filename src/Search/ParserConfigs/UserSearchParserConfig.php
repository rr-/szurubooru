<?php
namespace Szurubooru\Search\ParserConfigs;
use Szurubooru\NotSupportedException;
use Szurubooru\Search\Filters\IFilter;
use Szurubooru\Search\Filters\UserFilter;
use Szurubooru\Search\ParserConfigs\AbstractSearchParserConfig;
use Szurubooru\Search\Tokens\NamedSearchToken;
use Szurubooru\Search\Tokens\SearchToken;

class UserSearchParserConfig extends AbstractSearchParserConfig
{
    public function __construct()
    {
        $this->defineOrder(UserFilter::ORDER_NAME, ['name']);
        $this->defineOrder(UserFilter::ORDER_CREATION_TIME, ['creation_time', 'creation_date']);
    }

    public function createFilter()
    {
        return new UserFilter;
    }
}
