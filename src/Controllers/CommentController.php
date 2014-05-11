<?php
class CommentController
{
	public function listView($page = 1)
	{
		$ret = Api::run(
			new ListCommentsJob(),
			[
				JobArgs::ARG_PAGE_NUMBER => $page,
			]);

		$context = getContext();
		$context->transport->posts = $ret->entities;
		$context->transport->paginator = $ret;
	}

	public function addAction()
	{
		if (InputHelper::get('sender') == 'preview')
		{
			$comment = Api::run(
				new PreviewCommentJob(),
				[
					JobArgs::ARG_POST_ID => InputHelper::get('post-id'),
					JobArgs::ARG_NEW_TEXT => InputHelper::get('text')
				]);

			getContext()->transport->textPreview = $comment->getTextMarkdown();
		}

		Api::run(
			new AddCommentJob(),
			[
				JobArgs::ARG_POST_ID => InputHelper::get('post-id'),
				JobArgs::ARG_NEW_TEXT => InputHelper::get('text')
			]);
	}

	public function editView($id)
	{
		getContext()->transport->comment = CommentModel::getById($id);
	}

	public function editAction($id)
	{
		if (InputHelper::get('sender') == 'preview')
		{
			$comment = Api::run(
				new PreviewCommentJob(),
				[
					JobArgs::ARG_COMMENT_ID => $id,
					JobArgs::ARG_NEW_TEXT => InputHelper::get('text')
				]);

			getContext()->transport->textPreview = $comment->getTextMarkdown();
		}

		Api::run(
			new EditCommentJob(),
			[
				JobArgs::ARG_COMMENT_ID => $id,
				JobArgs::ARG_NEW_TEXT => InputHelper::get('text')
			]);
	}

	public function deleteAction($id)
	{
		$comment = Api::run(
			new DeleteCommentJob(),
			[
				JobArgs::ARG_COMMENT_ID => $id,
			]);
	}
}
