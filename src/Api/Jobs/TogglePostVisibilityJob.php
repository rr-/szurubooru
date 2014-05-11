<?php
class TogglePostVisibilityJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;
		$visible = boolval($this->getArgument(JobArgs::ARG_NEW_STATE));

		$post->setHidden(!$visible);
		PostModel::save($post);

		Logger::log(
			$visible
				? '{user} unhidden {post}'
				: '{user} hidden {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::HidePost,
			Access::getIdentity($this->post->getUploader()));
	}
}
