<?php
class ListTagsJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function  __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(Core::getConfig()->browsing->tagsPerPage);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$query = $this->hasArgument(JobArgs::ARG_QUERY)
			? $this->getArgument(JobArgs::ARG_QUERY)
			: '';

		$tags = TagSearchService::getEntities($query, $pageSize, $page);
		$tagCount = TagSearchService::getEntityCount($query);

		return $this->pager->serialize($tags, $tagCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			JobArgs::Optional(JobArgs::ARG_QUERY));
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ListTags;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
