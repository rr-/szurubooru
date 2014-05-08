<?php
abstract class AbstractPostJob extends AbstractJob
{
	protected $post;

	public function prepare()
	{
		if ($this->hasArgument(self::POST_ENTITY))
		{
			$this->post = $this->getArgument(self::POST_ENTITY);
		}
		else
		{
			$postId = $this->getArgument(self::POST_ID);
			$this->post = PostModel::getByIdOrName($postId);
		}
	}
}
