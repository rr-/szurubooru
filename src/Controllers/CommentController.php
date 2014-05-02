<?php
class CommentController
{
	public function listView($page = 1)
	{
		$ret = Api::run(
			new ListCommentsJob(),
			[
				JobArgs::PAGE_NUMBER => $page,
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
