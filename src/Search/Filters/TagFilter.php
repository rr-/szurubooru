<?php
namespace Szurubooru\Search\Filters;

class TagFilter extends BasicFilter implements IFilter
{
    const ORDER_ID = 'id';
    const ORDER_NAME = 'name';
    const ORDER_CREATION_TIME = 'creationTime';
    const ORDER_LAST_EDIT_TIME = 'lastEditTime';
    const ORDER_USAGE_COUNT = 'usages';

    const REQUIREMENT_PARTIAL_TAG_NAME = 'partialTagName';
    const REQUIREMENT_CATEGORY = 'category';

    public function __construct()
    {
        $this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
    }
}
