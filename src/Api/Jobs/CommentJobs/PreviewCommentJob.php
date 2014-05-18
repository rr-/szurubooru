<?php
class PreviewCommentJob extends AbstractJob
{
	protected $commentRetriever;
	protected $postRetriever;

	public function __construct()
	{
		$this->commentRetriever = new CommentRetriever($this);
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$user = Auth::getCurrentUser();
		$text = $this->getArgument(JobArgs::ARG_NEW_TEXT);

		$comment = $this->commentRetriever->tryRetrieve();
		if (!$comment)
		{
			$post = $this->postRetriever->retrieve();
			$comment = CommentModel::spawn();
			$comment->setPost($post);
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
				$this->commentRetriever->getRequiredArguments(),
				$this->postRetriever->getRequiredArguments()));
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::AddComment;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return Core::getConfig()->registration->needEmailForCommenting;
	}
}
