<?php
class AddCommentJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
		$user = Auth::getCurrentUser();
		$text = $this->getArgument(JobArgs::ARG_NEW_TEXT);

		$comment = CommentModel::spawn();
		$comment->setCommenter($user);
		$comment->setPost($post);
		$comment->setCreationTime(time());
		$comment->setText($text);

		CommentModel::save($comment);
		Logger::log('{user} commented on {post}', [
			'user' => TextHelper::reprUser($user),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_TEXT);
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
