<?php
class ListPostsJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function  __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(getConfig()->browsing->postsPerPage);
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

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ListPosts);
	}
}
