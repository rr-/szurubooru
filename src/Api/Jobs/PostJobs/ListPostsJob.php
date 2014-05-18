<?php
class ListPostsJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function  __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(Core::getConfig()->browsing->postsPerPage);
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

		$posts = PostSearchService::getEntities($query, $pageSize, $page);
		$postCount = PostSearchService::getEntityCount($query);

		PostModel::preloadTags($posts);

		return $this->pager->serialize($posts, $postCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			JobArgs::Optional(JobArgs::ARG_QUERY));
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ListPosts;
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
