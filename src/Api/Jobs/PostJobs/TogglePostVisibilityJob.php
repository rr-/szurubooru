<?php
class TogglePostVisibilityJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();
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

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_STATE);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::HidePost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
	}
}
