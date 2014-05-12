<?php
class DeletePostJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

		PostModel::remove($post);

		Logger::log('{user} deleted {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($post)]);
	}

	public function getRequiredArguments()
	{
		return $this->postRetriever->getRequiredArguments();
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::DeletePost,
			Access::getIdentity($this->postRetriever->retrieve()->getUploader()));
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
