<?php
namespace Szurubooru\Search\Filters;

class CommentFilter extends BasicFilter implements IFilter
{
	const ORDER_ID = 'id';

	const REQUIREMENT_POST_ID = 'postId';

	public function __construct()
	{
		$this->setOrder([self::ORDER_ID => self::ORDER_DESC]);
	}
}
