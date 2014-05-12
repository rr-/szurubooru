<?php
class FeaturePostJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

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

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::Optional(JobArgs::ARG_ANONYMOUS));
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::FeaturePost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}

	public function isAuthenticationRequired()
	{
		return true;
	}
}
