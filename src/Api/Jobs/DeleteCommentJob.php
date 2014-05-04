<?php
class DeleteCommentJob extends AbstractJob
{
	protected $comment;

	public function prepare()
	{
		$this->comment = CommentModel::findById($this->getArgument(self::COMMENT_ID));
	}

	public function execute()
	{
		$post = $this->comment->getPost();

		CommentModel::remove($this->comment);

		LogHelper::log('{user} removed comment from {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::DeleteComment,
			Access::getIdentity($this->comment->getCommenter()));
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
