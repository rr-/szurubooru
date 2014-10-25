<?php
namespace Szurubooru\Entities;

final class PostNote extends Entity
{
	private $postId;
	private $left;
	private $top;
	private $width;
	private $height;
	private $text;

	public function getPostId()
	{
		return $this->postId;
	}

	public function setPostId($postId)
	{
		$this->postId = $postId;
	}

	public function getLeft()
	{
		return $this->left;
	}

	public function setLeft($left)
	{
		$this->left = $left;
	}

	public function getTop()
	{
		return $this->top;
	}

	public function setTop($top)
	{
		$this->top = $top;
	}

	public function getWidth()
	{
		return $this->width;
	}

	public function setWidth($width)
	{
		$this->width = $width;
	}

	public function getHeight()
	{
		return $this->height;
	}

	public function setHeight($height)
	{
		$this->height = $height;
	}

	public function getText()
	{
		return $this->text;
	}

	public function setText($text)
	{
		$this->text = $text;
	}
}
