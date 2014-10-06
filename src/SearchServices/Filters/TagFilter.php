<?php
namespace Szurubooru\SearchServices\Filters;

class TagFilter extends BasicFilter implements IFilter
{
	const ORDER_ID = 'id';
	const ORDER_NAME = 'name';
	const ORDER_CREATION_TIME = 'creationTime';
	const ORDER_USAGE_COUNT = 'usages';

	public function __construct()
	{
		$this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
	}
}
