<?php
class CommentController
{
	/**
	* @route /comments
	* @route /comments/{page}
	* @validate page [0-9]+
	*/
	public function listAction($page)
	{
		PrivilegesHelper::confirmWithException(Privilege::ListComments);

		$page = max(1, intval($page));
		$commentsPerPage = intval($this->config->comments->commentsPerPage);
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

		$this->context->postGroups = true;
		$this->context->transport->posts = $posts;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $postCount;
		$this->context->transport->paginator->entities = $posts;
		$this->context->transport->paginator->params = func_get_args();
	}



	/**
	* @route /post/{postId}/add-comment
	* @valdiate postId [0-9]+
	*/
	public function addAction($postId)
	{
		PrivilegesHelper::confirmWithException(Privilege::AddComment);
		if ($this->config->registration->needEmailForCommenting)
			PrivilegesHelper::confirmEmail($this->context->user);

		$post = PostModel::findById($postId);
		$this->context->transport->post = $post;

		if (InputHelper::get('submit'))
		{
			$text = InputHelper::get('text');
			$text = CommentModel::validateText($text);

			$comment = CommentModel::spawn();
			$comment->setPost($post);
			if ($this->context->loggedIn)
				$comment->setCommenter($this->context->user);
			else
				$comment->setCommenter(null);
			$comment->commentDate = time();
			$comment->text = $text;

			if (InputHelper::get('sender') != 'preview')
			{
				CommentModel::save($comment);
				LogHelper::log('{user} commented on {post}', ['post' => TextHelper::reprPost($post->id)]);
			}
			$this->context->transport->textPreview = $comment->getText();
			StatusHelper::success();
		}
	}



	/**
	* @route /comment/{id}/edit
	* @validate id [0-9]+
	*/
	public function editAction($id)
	{
		$comment = CommentModel::findById($id);
		$this->context->transport->comment = $comment;

		PrivilegesHelper::confirmWithException(Privilege::EditComment, PrivilegesHelper::getIdentitySubPrivilege($comment->getCommenter()));

		if (InputHelper::get('submit'))
		{
			$text = InputHelper::get('text');
			$text = CommentModel::validateText($text);

			$comment->text = $text;

			if (InputHelper::get('sender') != 'preview')
			{
				CommentModel::save($comment);
				LogHelper::log('{user} edited comment in {post}', ['post' => TextHelper::reprPost($comment->getPost())]);
			}
			$this->context->transport->textPreview = $comment->getText();
			StatusHelper::success();
		}
	}



	/**
	* @route /comment/{id}/delete
	* @validate id [0-9]+
	*/
	public function deleteAction($id)
	{
		$comment = CommentModel::findById($id);

		PrivilegesHelper::confirmWithException(Privilege::DeleteComment, PrivilegesHelper::getIdentitySubPrivilege($comment->getCommenter()));
		CommentModel::remove($comment);

		LogHelper::log('{user} removed comment from {post}', ['post' => TextHelper::reprPost($comment->getPost())]);
		StatusHelper::success();
	}
}
