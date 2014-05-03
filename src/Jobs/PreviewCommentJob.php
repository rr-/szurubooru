<?php
class PreviewCommentJob extends AbstractJob
{
	public function execute()
	{
		$user = Auth::getCurrentUser();
		$text = CommentModel::validateText($this->getArgument(self::TEXT));

		$comment = CommentModel::spawn();
		$comment->setCommenter($user);
		$comment->commentDate = time();
		$comment->text = $text;
		return $comment;
	}

	public function requiresPrivilege()
	{
		return Privilege::AddComment;
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
