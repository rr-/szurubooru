<?php
namespace Szurubooru\SearchServices\Filters;

class BasicFilter implements IFilter
{
	private $order;
	private $requirements = [];
	private $pageNumber;
	private $pageSize;

	public function __construct()
	{
		$this->setOrder(['id' => self::ORDER_DESC]);
	}

	public function getOrder()
	{
		return $this->order;
	}

	public function setOrder($order)
	{
		$this->order = $order;
	}

	public function addRequirement(\Szurubooru\SearchServices\Requirement $requirement)
	{
		$this->requirements[] = $requirement;
	}

	public function getRequirements()
	{
		return $this->requirements;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
	}

	public function getPageNumber()
	{
		return $this->pageNumber;
	}

	public function setPageNumber($pageNumber)
	{
		$this->pageNumber = $pageNumber;
	}
}
