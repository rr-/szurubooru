<?php
namespace Szurubooru\Search\Filters;

class UserFilter extends BasicFilter implements IFilter
{
    const ORDER_NAME = 'name';
    const ORDER_CREATION_TIME = 'creationTime';

    public function __construct()
    {
        $this->setOrder([self::ORDER_NAME => self::ORDER_ASC]);
    }
}
