<?php
class ListUsersJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function  __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(getConfig()->browsing->usersPerPage);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$filter = $this->hasArgument(JobArgs::ARG_QUERY)
			? $this->getArgument(JobArgs::ARG_QUERY)
			: '';

		$users = UserSearchService::getEntities($filter, $pageSize, $page);
		$userCount = UserSearchService::getEntityCount($filter);

		return $this->pager->serialize($users, $userCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			JobArgs::Optional(JobArgs::ARG_QUERY));
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ListUsers);
	}
}
