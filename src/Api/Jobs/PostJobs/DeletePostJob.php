<?php
class DeletePostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		PostModel::remove($post);

		Logger::log('{user} deleted {post}', [
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
			Privilege::DeletePost,
			Access::getIdentity($this->post->getUploader()));
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
