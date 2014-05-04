<?php
class FeaturePostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		PropertyModel::set(PropertyModel::FeaturedPostId, $post->id);
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, Auth::getCurrentUser()->name);

		LogHelper::log('{user} featured {post} on main page', [
			'user' => TextHelper::reprPost(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::FeaturePost,
			Access::getIdentity($this->post->getUploader())
		];
	}

	public function requiresAuthentication()
	{
		return true;
	}
}
