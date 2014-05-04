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

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::DeletePost,
			Access::getIdentity($this->post->getUploader()));
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
