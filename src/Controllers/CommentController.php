<?php
class CommentController
{
	public function listAction($page)
	{
		Access::assert(Privilege::ListComments);

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

		$context = getContext();
		$context->postGroups = true;
		$context->transport->posts = $posts;
		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $page;
		$context->transport->paginator->pageCount = $pageCount;
		$context->transport->paginator->entityCount = $postCount;
		$context->transport->paginator->entities = $posts;
		$context->transport->paginator->params = func_get_args();
	}

	public function addAction($postId)
	{
		$context = getContext();
		Access::assert(Privilege::AddComment);
		if (getConfig()->registration->needEmailForCommenting)
			Access::assertEmailConfirmation();

		$post = PostModel::findById($postId);
		$context->transport->post = $post;

		if (!InputHelper::get('submit'))
			return;

		$text = InputHelper::get('text');
		$text = CommentModel::validateText($text);

		$comment = CommentModel::spawn();
		$comment->setPost($post);
		if (Auth::isLoggedIn())
			$comment->setCommenter(Auth::getCurrentUser());
		else
			$comment->setCommenter(null);
		$comment->commentDate = time();
		$comment->text = $text;

		if (InputHelper::get('sender') != 'preview')
		{
			CommentModel::save($comment);
			LogHelper::log('{user} commented on {post}', ['post' => TextHelper::reprPost($post->id)]);
		}
		$context->transport->textPreview = $comment->getText();
		StatusHelper::success();
	}

	public function editAction($id)
	{
		$context = getContext();
		$comment = CommentModel::findById($id);
		$context->transport->comment = $comment;

		Access::assert(
			Privilege::EditComment,
			Access::getIdentity($comment->getCommenter()));

		if (!InputHelper::get('submit'))
			return;

		$text = InputHelper::get('text');
		$text = CommentModel::validateText($text);

		$comment->text = $text;

		if (InputHelper::get('sender') != 'preview')
		{
			CommentModel::save($comment);
			LogHelper::log('{user} edited comment in {post}', [
				'post' => TextHelper::reprPost($comment->getPost())]);
		}
		$context->transport->textPreview = $comment->getText();
		StatusHelper::success();
	}

	public function deleteAction($id)
	{
		$comment = CommentModel::findById($id);

		Access::assert(
			Privilege::DeleteComment,
			Access::getIdentity($comment->getCommenter()));

		CommentModel::remove($comment);

		LogHelper::log('{user} removed comment from {post}', [
			'post' => TextHelper::reprPost($comment->getPost())]);
		StatusHelper::success();
	}
}
