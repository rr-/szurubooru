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
		$this->context->stylesheets []= 'post-small.css';
		$this->context->stylesheets []= 'comment-list.css';
		$this->context->stylesheets []= 'comment-small.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';

		$page = intval($page);
		$commentsPerPage = intval($this->config->comments->commentsPerPage);
		$this->context->subTitle = 'comments';
		PrivilegesHelper::confirmWithException(Privilege::ListComments);

		$commentCount = Model_Comment::getEntityCount(null);
		$pageCount = ceil($commentCount / $commentsPerPage);
		$page = max(1, min($pageCount, $page));
		$comments = Model_Comment::getEntities(null, $commentsPerPage, $page);

		$this->context->postGroups = true;
		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $commentCount;
		$this->context->transport->paginator->entities = $comments;
		$this->context->transport->paginator->params = func_get_args();
		$this->context->transport->comments = $comments;
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

		$post = Model_Post::locate($postId);

		if (InputHelper::get('submit'))
		{
			$text = InputHelper::get('text');
			$text = Model_Comment::validateText($text);

			$comment = Model_Comment::create();
			$comment->post = $post;
			if ($this->context->loggedIn)
				$comment->commenter = $this->context->user;
			$comment->comment_date = time();
			$comment->text = $text;
			if (InputHelper::get('sender') != 'preview')
			{
				Model_Comment::save($comment);
				LogHelper::logEvent('comment-add', '{user} commented on {post}', ['post' => TextHelper::reprPost($post->id)]);
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
		$comment = Model_Comment::locate($id);
		PrivilegesHelper::confirmWithException(Privilege::DeleteComment, PrivilegesHelper::getIdentitySubPrivilege($comment->commenter));
		Model_Comment::remove($comment);

		LogHelper::logEvent('comment-del', '{user} removed comment from {post}', ['post' => TextHelper::reprPost($comment->post)]);
		StatusHelper::success();
	}
}
