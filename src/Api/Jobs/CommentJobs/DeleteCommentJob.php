<?php
class DeleteCommentJob extends AbstractJob
{
	protected $commentRetriever;

	public function __construct()
	{
		$this->commentRetriever = new CommentRetriever($this);
	}

	public function execute()
	{
		$comment = $this->commentRetriever->retrieve();
		$post = $comment->getPost();

		CommentModel::remove($comment);

		Logger::log('{user} removed comment from {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);
	}

	public function getRequiredArguments()
	{
		return $this->commentRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::DeleteComment,
			Access::getIdentity($this->commentRetriever->retrieve()->getCommenter()));
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
