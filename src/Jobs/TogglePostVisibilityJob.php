<?php
class TogglePostVisibilityJob extends AbstractPostEditJob
{
	public function execute()
	{
		$post = $this->post;
		$visible = boolval($this->getArgument(self::STATE));

		$post->setHidden(!$visible);
		PostModel::save($post);

		LogHelper::log(
			$visible
				? '{user} unhidden {post}'
				: '{user} hidden {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::HidePost,
			Access::getIdentity($this->post->getUploader())
		];
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
