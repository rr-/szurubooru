<?php
class GetPostThumbJob extends AbstractJob
{
	protected $postRetriever;

	public function __construct()
	{
		$this->postRetriever = new SafePostRetriever($this);
	}

	public function execute()
	{
		//optimize - save extra query to DB
		if ($this->hasArgument(JobArgs::ARG_POST_NAME))
			$name = $this->getArgument(JobArgs::ARG_POST_NAME);
		else
		{
			$post = $this->postRetriever->retrieve();
			$name = $post->getName();
		}

		$path = PostModel::tryGetWorkingThumbPath($name);
		if (!$path)
		{
			$post = PostModel::getByName($name);
			$post = $this->postRetriever->retrieve();

			$post->generateThumb();
			$path = PostModel::tryGetWorkingThumbPath($name);

			if (!$path)
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

	public function getRequiredPrivileges()
	{
		return false;
	}
}
