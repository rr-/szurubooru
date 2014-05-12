<?php
class ListCommentsJob extends AbstractPageJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(JobArgs::ARG_PAGE_NUMBER);
		$query = 'comment_min:1 order:comment_date,desc';

		$posts = PostSearchService::getEntities($query, $pageSize, $page);
		$postCount = PostSearchService::getEntityCount($query);

		PostModel::preloadTags($posts);
		PostModel::preloadComments($posts);
		$comments = [];
		foreach ($posts as $post)
			$comments = array_merge($comments, $post->getComments());
		CommentModel::preloadCommenters($comments);

		return $this->getPager($posts, $postCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->comments->commentsPerPage);
	}

	public function getRequiredSubArguments()
	{
		return null;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ListComments);
	}
}
