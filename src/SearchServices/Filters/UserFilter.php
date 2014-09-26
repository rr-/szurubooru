<?php
namespace Szurubooru\SearchServices\Filters;

class UserFilter extends BasicFilter implements IFilter
{
	const ORDER_NAME = 'name';
	const ORDER_REGISTRATION_TIME = 'registrationTime';
}
