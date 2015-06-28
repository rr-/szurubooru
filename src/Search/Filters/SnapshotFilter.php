<?php
namespace Szurubooru\Search\Filters;

class SnapshotFilter extends BasicFilter implements IFilter
{
    const ORDER_ID = 'id';

    const REQUIREMENT_PRIMARY_KEY = 'primaryKey';
    const REQUIREMENT_TYPE = 'type';

    public function __construct()
    {
        $this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
    }
}
