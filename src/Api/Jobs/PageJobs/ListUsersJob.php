<?php
class ListUsersJob extends AbstractPageJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(JobArgs::ARG_PAGE_NUMBER);
		$filter = $this->getArgument(JobArgs::ARG_QUERY);

		$users = UserSearchService::getEntities($filter, $pageSize, $page);
		$userCount = UserSearchService::getEntityCount($filter);

		return $this->getPager($users, $userCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->browsing->usersPerPage);
	}

	public function getRequiredSubArguments()
	{
		return JobArgs::ARG_QUERY;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ListUsers);
	}
}
