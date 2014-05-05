<?php
class PreviewCommentJob extends AbstractJob
{
	public function execute()
	{
		$user = Auth::getCurrentUser();
		$text = $this->getArgument(self::TEXT);
		$post = PostModel::findById($this->getArgument(self::POST_ID));

		$comment = CommentModel::spawn();
		$comment->setCommenter($user);
		$comment->setDateTime(time());
		$comment->setText($text);
		$comment->setPost($post);

		$comment->validate();

		return $comment;
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::AddComment);
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
