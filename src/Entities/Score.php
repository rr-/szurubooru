<?php
namespace Szurubooru\Entities;

class Score extends Entity
{
	private $postId;
	private $userId;
	private $time;
	private $score;

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

	public function getTime()
	{
		return $this->time;
	}

	public function setTime($time)
	{
		$this->time = $time;
	}

	public function getScore()
	{
		return $this->score;
	}

	public function setScore($score)
	{
		$this->score = $score;
	}

	public function getUser()
	{
		return $this->lazyLoad(self::LAZY_LOADER_USER, null);
	}

	public function setUser(\Szurubooru\Entities\User $user)
	{
		$this->lazySave(self::LAZY_LOADER_USER, $user);
		$this->userId = $user->getId();
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
