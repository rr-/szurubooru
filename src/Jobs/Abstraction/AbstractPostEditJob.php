<?php
abstract class AbstractPostEditJob extends AbstractJob
{
	protected $post;

	public function prepare()
	{
		$postId = $this->getArgument(JobArgs::POST_ID);
		$this->post = PostModel::findByIdOrName($postId);
	}
}
