<?php
class FeaturePostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		PropertyModel::set(PropertyModel::FeaturedPostId, $post->getId());
		PropertyModel::set(PropertyModel::FeaturedPostDate, time());
		PropertyModel::set(PropertyModel::FeaturedPostUserName, Auth::getCurrentUser()->getName());

		Logger::log('{user} featured {post} on main page', [
			'user' => TextHelper::reprPost(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);

		return $post;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::FeaturePost,
			Access::getIdentity($this->post->getUploader()));
	}

	public function requiresAuthentication()
	{
		return true;
	}
}
