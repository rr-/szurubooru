<?php
namespace Szurubooru\Controllers\ViewProxies;

class CommentViewProxy extends AbstractViewProxy
{
	private $postViewProxy;
	private $userViewProxy;

	public function __construct(
		PostViewProxy $postViewProxy,
		UserViewProxy $userViewProxy)
	{
		$this->postViewProxy = $postViewProxy;
		$this->userViewProxy = $userViewProxy;
	}

	public function fromEntity($comment, $config = [])
	{
		$result = new \StdClass;
		if ($comment)
		{
			$result->id = $comment->getId();
			$result->creationTime = $comment->getCreationTime();
			$result->lastEditTime = $comment->getLastEditTime();
			$result->text = $comment->getText();
			$result->postId = $comment->getPostId();
			$result->user = $this->userViewProxy->fromEntity($comment->getUser());
		}
		return $result;
	}
}

