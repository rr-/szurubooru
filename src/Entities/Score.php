<?php
namespace Szurubooru\Entities;

final class Score extends Entity
{
	private $postId;
	private $commentId;
	private $score;
	private $time;
	private $userId;

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

	public function getCommentId()
	{
		return $this->commentId;
	}

	public function setCommentId($commentId)
	{
		$this->commentId = $commentId;
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
}
