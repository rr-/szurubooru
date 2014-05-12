<?php
class JobPager
{
	private $job;

	public function __construct(IJob $job)
	{
		$this->pageSize = 20;
		$this->job = $job;
	}

	public function setPageSize($newPageSize)
	{
		$this->pageSize = $newPageSize;
	}

	public function getPageSize()
	{
		return $this->pageSize;
	}

	public function getPageNumber()
	{
		if ($this->job->hasArgument(JobArgs::ARG_PAGE_NUMBER))
			return (int) $this->job->getArgument(JobArgs::ARG_PAGE_NUMBER);
		return 1;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER);
	}

	public function serialize($entities, $totalEntityCount)
	{
		$pageSize = $this->getPageSize();
		$pageNumber = $this->getPageNumber();

		$pageCount = ceil($totalEntityCount / $pageSize);
		$pageNumber = $this->getPageNumber();
		$pageNumber = min($pageCount, $pageNumber);

		$ret = new StdClass;
		$ret->entities = $entities;
		$ret->entityCount = $totalEntityCount;
		$ret->page = $pageNumber;
		$ret->pageCount = $pageCount;
		return $ret;
	}
}
