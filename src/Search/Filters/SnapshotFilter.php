<?php
namespace Szurubooru\Search\Filters;

class SnapshotFilter extends BasicFilter implements IFilter
{
    const ORDER_ID = 'id';

    const REQUIREMENT_PRIMARY_KEY = 'snapshots.primaryKey';
    const REQUIREMENT_TYPE = 'snapshots.type';

    public function __construct()
    {
        $this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
    }
}
