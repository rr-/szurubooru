<?php
class GetPostJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new PostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

		CommentModel::preloadCommenters($post->getComments());

		return $post;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			null);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ViewPost;
	}

	public function getRequiredSubPrivileges()
	{
		$post = $this->postRetriever->retrieve();
		$privileges = [];

		if ($post->isHidden())
			$privileges []= 'hidden';

		$privileges []= $post->getSafety()->toString();

		return $privileges;
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
