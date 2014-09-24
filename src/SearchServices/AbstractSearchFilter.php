<?php
namespace Szurubooru\SearchServices;

abstract class AbstractSearchFilter
{
	const ORDER_ASC = 1;
	const ORDER_DESC = -1;

	private $order;

	public function getOrder()
	{
		return $this->order;
	}

	public function setOrder($order)
	{
		$this->order = $order;
	}

	public function __construct()
	{
		$this->setOrder(['id' => self::ORDER_DESC]);
	}
}
