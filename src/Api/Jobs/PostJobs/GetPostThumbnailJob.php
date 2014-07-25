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
		if (!$this->isValidThumbnailPath($path))
		{
			try
			{
				$post->generateThumbnail();
				$path = $post->getThumbnailPath();
			}
			catch (Exception $e)
			{
				$path = null;
			}

			if (!$this->isValidThumbnailPath($path))
				$path = $this->getDefaultThumbnailPath();
		}

		return new ApiFileOutput($path, 'thumbnail.jpg');
	}

	private function isValidThumbnailPath($path)
	{
		return file_exists($path) and is_readable($path);
	}

	private function getDefaultThumbnailPath()
	{
		$path = Core::getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
		$path = TextHelper::absolutePath($path);
		return $path;
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
