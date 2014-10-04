<?php
namespace Szurubooru\Entities;

class Comment extends Entity
{
	private $postId;
	private $userId;
	private $creationTime;
	private $lastEditTime;
	private $text;

	const LAZY_LOADER_USER = 'user';
	const LAZY_LOADER_POST = 'post';

	public function getUserId()
	{
		return $this->userId;
	}

	public function setUserId($userId)
	{
		$this->userId = $userId;
	}

	public function getPostId()
	{
		return $this->postId;
	}

	public function setPostId($postId)
	{
		$this->postId = $postId;
	}

	public function getCreationTime()
	{
		return $this->creationTime;
	}

	public function setCreationTime($creationTime)
	{
		$this->creationTime = $creationTime;
	}

	public function getLastEditTime()
	{
		return $this->lastEditTime;
	}

	public function setLastEditTime($lastEditTime)
	{
		$this->lastEditTime = $lastEditTime;
	}

	public function getText()
	{
		return $this->text;
	}

	public function setText($text)
	{
		$this->text = $text;
	}

	public function getUser()
	{
		return $this->lazyLoad(self::LAZY_LOADER_USER, null);
	}

	public function setUser(\Szurubooru\Entities\User $user = null)
	{
		$this->lazySave(self::LAZY_LOADER_USER, $user);
		$this->userId = $user ? $user->getId() : null;
	}

	public function getPost()
	{
		return $this->lazyLoad(self::LAZY_LOADER_POST, null);
	}

	public function setPost(\Szurubooru\Entities\Post $post)
	{
		$this->lazySave(self::LAZY_LOADER_POST, $post);
		$this->postId = $post->getId();
	}
}
