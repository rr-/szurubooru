<?php
namespace Szurubooru\Search\Filters;

class UserFilter extends BasicFilter implements IFilter
{
    const ORDER_NAME = 'name';
    const ORDER_REGISTRATION_TIME = 'registrationTime';

    public function __construct()
    {
        $this->setOrder([self::ORDER_NAME => self::ORDER_ASC]);
    }
}
