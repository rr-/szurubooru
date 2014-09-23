<?php
namespace Szurubooru\SearchServices;

abstract class AbstractSearchFilter
{
	const ORDER_ASC = 1;
	const ORDER_DESC = -1;

	public $order;

	public function __construct()
	{
		$this->order = [];
	}
}
