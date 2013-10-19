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
		$this->context->subTitle = 'comments';
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';

		$page = intval($page);
		$commentsPerPage = intval($this->config->comments->commentsPerPage);
		PrivilegesHelper::confirmWithException(Privilege::ListComments);

		$buildDbQuery = function($dbQuery)
		{
			$dbQuery->from('comment');
			$dbQuery->orderBy('comment_date')->desc();
		};

		$countDbQuery = R::$f->begin();
		$countDbQuery->select('COUNT(1)')->as('count');
		$buildDbQuery($countDbQuery);
		$commentCount = intval($countDbQuery->get('row')['count']);
		$pageCount = ceil($commentCount / $commentsPerPage);
		$page = max(1, min($pageCount, $page));

		$searchDbQuery = R::$f->begin();
		$searchDbQuery->select('comment.*');
		$buildDbQuery($searchDbQuery);
		$searchDbQuery->limit('?')->put($commentsPerPage);
		$searchDbQuery->offset('?')->put(($page - 1) * $commentsPerPage);

		$comments = $searchDbQuery->get();
		$comments = R::convertToBeans('comment', $comments);
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
			$comment->commenter = $this->context->user;
			$comment->comment_date = time();
			$comment->text = $text;
			if (InputHelper::get('sender') != 'preview')
				R::store($comment);
			$this->context->transport->textPreview = $comment->getText();
			$this->context->transport->success = true;
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
		R::trash($comment);
		$this->context->transport->success = true;
	}
}
