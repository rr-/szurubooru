<?php
abstract class AbstractPageJob extends AbstractJob
{
	protected $pageSize = null;

	public abstract function getDefaultPageSize();

	public function getPageSize()
	{
		return $this->pageSize === null
			? $this->getDefaultPageSize()
			: $this->pageSize;
	}

	public function setPageSize($pageSize)
	{
		$this->pageSize = $pageSize;
		return $this;
	}

	public function getPager($entities, $entityCount, $page, $pageSize)
	{
		$pageCount = ceil($entityCount / $pageSize);
		$page = min($pageCount, $page);

		$ret = new StdClass;
		$ret->entities = $entities;
		$ret->entityCount = $entityCount;
		$ret->page = $page;
		$ret->pageCount = $pageCount;
		return $ret;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::ARG_PAGE_NUMBER,
			$this->getRequiredSubArguments());
	}

	public abstract function getRequiredSubArguments();
}
