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
		elseif ($this->hasArgument(JobArgs::ARG_POST_ID))
		{
			$postId = $this->getArgument(JobArgs::ARG_POST_ID);
			$this->post = PostModel::getById($postId);
		}
		else
		{
			$postName = $this->getArgument(JobArgs::ARG_POST_NAME);
			$this->post = PostModel::getByName($postName);
		}
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::Alternative(
				JobArgs::ARG_POST_ID,
				JobArgs::ARG_POST_NAME,
				JobArgs::ARG_POST_ENTITY),
			$this->getRequiredSubArguments());
	}

	public abstract function getRequiredSubArguments();
}
