<?php
class DeleteCommentJob extends AbstractCommentJob
{
	public function execute()
	{
		$post = $this->comment->getPost();

		CommentModel::remove($this->comment);

		Logger::log('{user} removed comment from {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);
	}

	public function getRequiredSubArguments()
	{
		return null;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::DeleteComment,
			Access::getIdentity($this->comment->getCommenter()));
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
