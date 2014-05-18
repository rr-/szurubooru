<?php
class EditCommentJob extends AbstractJob
{
	protected $commentRetriever;

	public function __construct()
	{
		$this->commentRetriever = new CommentRetriever($this);
	}

	public function execute()
	{
		$comment = $this->commentRetriever->retrieve();

		$comment->setCreationTime(time());
		$comment->setText($this->getArgument(JobArgs::ARG_NEW_TEXT));

		CommentModel::save($comment);
		Logger::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->commentRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_TEXT);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::EditComment;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->commentRetriever->retrieve()->getCommenter());
	}

	public function isAuthenticationRequired()
	{
		return true;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
