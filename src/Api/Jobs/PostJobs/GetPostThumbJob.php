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

		$width = $this->hasArgument(JobArgs::ARG_THUMB_WIDTH) ? $this->getArgument(JobArgs::ARG_THUMB_WIDTH) : null;
		$height = $this->hasArgument(JobArgs::ARG_THUMB_HEIGHT) ? $this->getArgument(JobArgs::ARG_THUMB_HEIGHT) : null;

		$path = PostModel::tryGetWorkingThumbPath($name, $width, $height);
		if (!$path)
		{
			$post = PostModel::getByName($name);
			$post = $this->postRetriever->retrieve();

			$post->generateThumb($width, $height);
			$path = PostModel::tryGetWorkingThumbPath($name, $width, $height);

			if (!$path)
			{
				$path = getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
				$path = TextHelper::absolutePath($path);
			}
		}

		return new ApiFileOutput($path, 'thumbnail.jpg');
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->postRetriever->getRequiredArguments(),
			JobArgs::Optional(JobArgs::ARG_THUMB_WIDTH),
			JobArgs::Optional(JobArgs::ARG_THUMB_HEIGHT));
	}

	public function getRequiredPrivileges()
	{
		//privilege check removed to make thumbs faster
		return false;
	}
}
