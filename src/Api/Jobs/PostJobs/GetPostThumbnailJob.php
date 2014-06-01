<?php
class GetPostThumbnailJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new SafePostRetriever($this);
	}

	public function execute()
	{
		$post = $this->postRetriever->retrieve();

		$path = $post->getThumbnailPath();
		if (!file_exists($path) or !is_readable($path))
		{
			$post->generateThumbnail();
			$path = $post->getThumbnailPath();

			if (!file_exists($path) or !is_readable($path))
			{
				$path = Core::getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
				$path = TextHelper::absolutePath($path);
			}
		}

		return new ApiFileOutput($path, 'thumbnail.jpg');
	}

	public function getRequiredArguments()
	{
		return $this->postRetriever->getRequiredArguments();
	}

	public function getRequiredMainPrivilege()
	{
		return null;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
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
