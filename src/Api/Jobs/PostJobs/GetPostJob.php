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

	public function getRequiredPrivileges()
	{
		$post = $this->postRetriever->retrieve();
		$privileges = [];

		if ($post->isHidden())
			$privileges []= new Privilege(Privilege::ViewPost, 'hidden');

		$privileges []= new Privilege(Privilege::ViewPost, $post->getSafety()->toString());

		return $privileges;
	}
}
