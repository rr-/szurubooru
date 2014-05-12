<?php
abstract class AbstractCommentJob extends AbstractJob
{
	protected $comment;

	public function prepare()
	{
		if ($this->hasArgument(JobArgs::ARG_COMMENT_ENTITY))
		{
			$this->comment = $this->getArgument(JobArgs::ARG_COMMENT_ENTITY);
		}
		else
		{
			$commentId = $this->getArgument(JobArgs::ARG_COMMENT_ID);
			$this->comment = CommentModel::getById($commentId);
		}
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::Alternative(
				JobArgs::ARG_COMMENT_ID,
				JobArgs::ARG_COMMENT_ENTITY),
			$this->getRequiredSubArguments());
	}

	public abstract function getRequiredSubArguments();
}
