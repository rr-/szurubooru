<?php
abstract class AbstractPostJob extends AbstractJob
{
	protected $post;

	public function prepare()
	{
		if ($this->hasArgument(JobArgs::ARG_POST_ENTITY))
		{
			$this->post = $this->getArgument(JobArgs::ARG_POST_ENTITY);
		}
		else
		{
			$postId = $this->getArgument(JobArgs::ARG_POST_ID);
			$this->post = PostModel::getByIdOrName($postId);
		}
	}
}
