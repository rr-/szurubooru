<?php
namespace Szurubooru\Search\ParserConfigs;
use Szurubooru\Search\Filters\SnapshotFilter;

class SnapshotSearchParserConfig extends AbstractSearchParserConfig
{
    public function createFilter()
    {
        return new SnapshotFilter;
    }
}
