<?php
class CommentController
{
	public function listView($page)
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

	public function previewAction()
	{
		$comment = Api::run(
			new PreviewCommentJob(),
			[
				JobArgs::TEXT => InputHelper::get('text')
			]);

		getContext()->transport->textPreview = $comment->getText();
	}

	public function addAction()
	{
		if (InputHelper::get('sender') == 'preview')
			return $this->previewAction();

		Api::run(
			new AddCommentJob(),
			[
				JobArgs::POST_ID => InputHelper::get('post-id'),
				JobArgs::TEXT => InputHelper::get('text')
			]);
	}

	public function editView($id)
	{
		getContext()->transport->comment = CommentModel::findById($id);
	}

	public function editAction($id)
	{
		if (InputHelper::get('sender') == 'preview')
			return $this->previewAction();

		Api::run(
			new EditCommentJob(),
			[
				JobArgs::COMMENT_ID => $id,
				JobArgs::TEXT => InputHelper::get('text')
			]);
	}

	public function deleteAction($id)
	{
		$comment = Api::run(
			new DeleteCommentJob(),
			[
				JobArgs::COMMENT_ID => $id,
			]);
	}
}
