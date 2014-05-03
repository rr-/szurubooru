<?php
class ListPostsJob extends AbstractJob
{
	public function execute()
	{
		$page = $this->getArgument(self::PAGE_NUMBER);
		$query = $this->getArgument(self::QUERY);

		$page = max(1, intval($page));
		$postsPerPage = intval(getConfig()->browsing->postsPerPage);

		$posts = PostSearchService::getEntities($query, $postsPerPage, $page);
		$postCount = PostSearchService::getEntityCount($query);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = min($pageCount, $page);

		PostModel::preloadTags($posts);

		$ret = new StdClass;
		$ret->posts = $posts;
		$ret->postCount = $postCount;
		$ret->page = $page;
		$ret->pageCount = $pageCount;
		return $ret;
	}

	public function requiresPrivilege()
	{
		return Privilege::ListPosts;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
