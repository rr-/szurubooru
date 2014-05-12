<?php
class GetPostThumbJob extends AbstractJob
{
	public function execute()
	{
		if ($this->getArgument(JobArgs::ARG_POST_NAME))
			$name = $this->getArgument(JobArgs::ARG_POST_NAME);
		else
		{
			$post = PostModel::getByName($this->getArgument(JobArgs::ARG_POST_ENTITY));
			$name = $post->getName();
		}

		$width = $this->hasArgument(JobArgs::ARG_THUMB_WIDTH) ? $this->getArgument(JobArgs::ARG_THUMB_WIDTH) : null;
		$height = $this->hasArgument(JobArgs::ARG_THUMB_HEIGHT) ? $this->getArgument(JobArgs::ARG_THUMB_HEIGHT) : null;

		$path = PostModel::getThumbCustomPath($name, $width, $height);
		if (!file_exists($path))
		{
			$path = PostModel::getThumbDefaultPath($name, $width, $height);
			if (!file_exists($path))
			{
				$post = PostModel::getByName($name);

				if ($post->isHidden())
					Access::assert(new Privilege(Privilege::ListPosts, 'hidden'));
				Access::assert(new Privilege(Privilege::ListPosts, $post->getSafety()->toString()));

				$post->generateThumb($width, $height);

				if (!file_exists($path))
				{
					$path = getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg';
					$path = TextHelper::absolutePath($path);
				}
			}
		}

		if (!is_readable($path))
			throw new SimpleException('Thumbnail file is not readable');

		return new ApiFileOutput($path, 'thumbnail.jpg');
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::Alternative(
				JobArgs::ARG_POST_ENTITY,
				JobArgs::ARG_POST_NAME),
			JobArgs::Optional(JobArgs::ARG_THUMB_WIDTH),
			JobArgs::Optional(JobArgs::ARG_THUMB_HEIGHT));
	}

	public function getRequiredPrivileges()
	{
		//manually enforced in execute when post is retrieved
		return false;
	}
}
