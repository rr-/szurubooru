<?php
class ListPostsJob extends AbstractPageJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(JobArgs::ARG_PAGE_NUMBER);
		$query = $this->getArgument(JobArgs::ARG_QUERY);

		$posts = PostSearchService::getEntities($query, $pageSize, $page);
		$postCount = PostSearchService::getEntityCount($query);

		PostModel::preloadTags($posts);

		return $this->getPager($posts, $postCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->browsing->postsPerPage);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::ListPosts);
	}
}
