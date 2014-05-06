<?php
class PreviewCommentJob extends AbstractJob
{
	public function execute()
	{
		$user = Auth::getCurrentUser();
		$text = $this->getArgument(self::TEXT);

		if ($this->hasArgument(self::POST_ID))
		{
			$post = PostModel::findById($this->getArgument(self::POST_ID));
			$comment = CommentModel::spawn();
			$comment->setPost($post);
		}
		else
		{
			$comment = CommentModel::findById($this->getArgument(self::COMMENT_ID));
		}

		$comment->setCommenter($user);
		$comment->setDateTime(time());
		$comment->setText($text);

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
