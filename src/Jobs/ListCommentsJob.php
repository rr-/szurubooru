<?php
class ListCommentsJob extends AbstractJob
{
	public function execute()
	{
		$page = $this->getArgument(JobArgs::PAGE_NUMBER);

		$page = max(1, intval($page));
		$commentsPerPage = intval(getConfig()->comments->commentsPerPage);
		$searchQuery = 'comment_min:1 order:comment_date,desc';

		$posts = PostSearchService::getEntities($searchQuery, $commentsPerPage, $page);
		$postCount = PostSearchService::getEntityCount($searchQuery);
		$pageCount = ceil($postCount / $commentsPerPage);
		PostModel::preloadTags($posts);
		PostModel::preloadComments($posts);
		$comments = [];
		foreach ($posts as $post)
			$comments = array_merge($comments, $post->getComments());
		CommentModel::preloadCommenters($comments);

		$ret = new StdClass;
		$ret->posts = $posts;
		$ret->postCount = $postCount;
		$ret->page = $page;
		$ret->pageCount = $pageCount;
		return $ret;
	}

	public function requiresPrivilege()
	{
		return Privilege::ListComments;
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
