<?php
abstract class AbstractPostEditJob extends AbstractJob
{
	protected $post;

	public function prepare()
	{
		$postId = $this->getArgument(self::POST_ID);
		$this->post = PostModel::findByIdOrName($postId);
	}
}
