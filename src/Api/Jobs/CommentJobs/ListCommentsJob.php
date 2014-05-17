<?php
class ListCommentsJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(Core::getConfig()->comments->commentsPerPage);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$query = 'comment_min:1 order:comment_date,desc';

		$posts = PostSearchService::getEntities($query, $pageSize, $page);
		$postCount = PostSearchService::getEntityCount($query);

		PostModel::preloadTags($posts);
		PostModel::preloadComments($posts);
		$comments = [];
		foreach ($posts as $post)
			$comments = array_merge($comments, $post->getComments());
		CommentModel::preloadCommenters($comments);

		return $this->pager->serialize($posts, $postCount);
	}

	public function getRequiredArguments()
	{
		return $this->pager->getRequiredArguments();
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ListComments;
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
