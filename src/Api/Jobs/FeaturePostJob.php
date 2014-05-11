<?php
class FeaturePostJob extends AbstractPostJob
{
	public function execute()
	{
		$post = $this->post;

		PropertyModel::set(PropertyModel::FeaturedPostId, $post->getId());
		PropertyModel::set(PropertyModel::FeaturedPostUnixTime, time());

		PropertyModel::set(PropertyModel::FeaturedPostUserName,
			($this->hasArgument(JobArgs::ARG_ANONYMOUS) and $this->getArgument(JobArgs::ARG_ANONYMOUS))
			? null
			: Auth::getCurrentUser()->getName());

		Logger::log('{user} featured {post} on main page', [
			'user' => TextHelper::reprPost(PropertyModel::get(PropertyModel::FeaturedPostUserName)),
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
