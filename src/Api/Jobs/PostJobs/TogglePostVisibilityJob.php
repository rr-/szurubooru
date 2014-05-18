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
		$visible = TextHelper::toBoolean($this->getArgument(JobArgs::ARG_NEW_STATE));

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

	public function getRequiredMainPrivilege()
	{
		return Privilege::HidePost;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->postRetriever->retrieve()->getUploader());
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
