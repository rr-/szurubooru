<?php
class CommentController
{
	public function listView($page = 1)
	{
		$ret = Api::run(
			new ListCommentsJob(),
			[
				ListCommentsJob::PAGE_NUMBER => $page,
			]);

		$context = getContext();
		$context->transport->posts = $ret->posts;
		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $ret->page;
		$context->transport->paginator->pageCount = $ret->pageCount;
		$context->transport->paginator->entityCount = $ret->postCount;
		$context->transport->paginator->entities = $ret->posts;
	}

	public function previewAction()
	{
		$comment = Api::run(
			new PreviewCommentJob(),
			[
				PreviewCommentJob::TEXT => InputHelper::get('text')
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
				AddCommentJob::POST_ID => InputHelper::get('post-id'),
				AddCommentJob::TEXT => InputHelper::get('text')
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
				EditCommentJob::COMMENT_ID => $id,
				EditCommentJob::TEXT => InputHelper::get('text')
			]);
	}

	public function deleteAction($id)
	{
		$comment = Api::run(
			new DeleteCommentJob(),
			[
				DeleteCommentJob::COMMENT_ID => $id,
			]);
	}
}
