<?php
class EditCommentJob extends AbstractJob
{
	public function execute($arguments)
	{
		$user = Auth::getCurrentUser();
		$comment = CommentModel::findById($arguments['comment-id']);
		$text = CommentModel::validateText($arguments['text']);

		$comment->commentDate = time();
		$comment->text = $text;

		CommentModel::save($comment);
		LogHelper::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return Privilege::EditComment;
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
