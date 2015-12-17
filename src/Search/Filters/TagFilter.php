<?php
namespace Szurubooru\Search\Filters;

class TagFilter extends BasicFilter implements IFilter
{
    const ORDER_ID = 'tags.id';
    const ORDER_NAME = 'tags.name';
    const ORDER_CREATION_TIME = 'tags.creationTime';
    const ORDER_LAST_EDIT_TIME = 'tags.lastEditTime';
    const ORDER_USAGE_COUNT = 'tags.usages';

    const REQUIREMENT_PARTIAL_TAG_NAME = 'tags.partialTagName';
    const REQUIREMENT_CREATION_TIME = 'tags.creationTime';
    const REQUIREMENT_LAST_EDIT_TIME = 'tags.lastEditTime';
    const REQUIREMENT_CATEGORY = 'tags.category';
    const REQUIREMENT_USAGE_COUNT = 'tags.usages';

    public function __construct()
    {
        $this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
    }
}
