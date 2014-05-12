<?php
class PreviewCommentJob extends AbstractJob
{
	public function execute()
	{
		$user = Auth::getCurrentUser();
		$text = $this->getArgument(JobArgs::ARG_NEW_TEXT);

		if ($this->hasArgument(JobArgs::ARG_POST_ID))
		{
			$post = PostModel::getById($this->getArgument(JobArgs::ARG_POST_ID));
			$comment = CommentModel::spawn();
			$comment->setPost($post);
		}
		else
		{
			$comment = CommentModel::getById($this->getArgument(JobArgs::ARG_COMMENT_ID));
		}

		$comment->setCommenter($user);
		$comment->setCreationTime(time());
		$comment->setText($text);

		$comment->validate();

		return $comment;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::ARG_NEW_TEXT,
			JobArgs::Alternative(
				JobArgs::ARG_COMMENT_ID,
				JobArgs::ARG_POST_ID));
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::AddComment);
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
