<?php
namespace Szurubooru\SearchServices\Filters;
use Szurubooru\SearchServices\Requirements\Requirement;

class BasicFilter implements IFilter
{
	private $order = [];
	private $requirements = [];
	private $pageNumber;
	private $pageSize;

	public function getOrder()
	{
		return $this->order;
	}

	public function setOrder($order)
	{
		$this->order = $order;
	}

	public function addRequirement(Requirement $requirement)
	{
		$this->requirements[] = $requirement;
	}

	public function getRequirements()
	{
		return $this->requirements;
	}

	public function getRequirementsByType($type)
	{
		$requirements = [];
		foreach ($this->getRequirements() as $key => $requirement)
		{
			if ($requirement->getType() === $type)
				$requirements[$key] = $requirement;
		}
		return $requirements;
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
