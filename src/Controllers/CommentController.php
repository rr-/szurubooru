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

		R::preload($comments, ['commenter' => 'user', 'post', 'post.uploader' => 'user']);
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

		$text = InputHelper::get('text');
		if (!empty($text))
		{
			$text = Model_Comment::validateText($text);
			$comment = R::dispense('comment');
			$comment->post = $post;
			if ($this->context->loggedIn)
				$comment->commenter = $this->context->user;
			$comment->comment_date = time();
			$comment->text = $text;
			if (InputHelper::get('sender') != 'preview')
				R::store($comment);
			$this->context->transport->textPreview = $comment->getText();
			LogHelper::logEvent('comment-add', '+{user} commented on @{post}', ['post' => $post->id]);
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
		R::preload($comment, ['commenter' => 'user']);
		PrivilegesHelper::confirmWithException(Privilege::DeleteComment, PrivilegesHelper::getIdentitySubPrivilege($comment->commenter));
		LogHelper::logEvent('comment-del', '+{user} removed comment from @{post}', ['post' => $comment->post->id]);
		R::trash($comment);
		StatusHelper::success();
	}
}
