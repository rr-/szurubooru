<?php
class CommentEntity extends AbstractEntity
{
	public $text;
	public $postId;
	public $commentDate;
	public $commenterId;

	public function getText()
	{
		return TextHelper::parseMarkdown($this->text);
	}

	public function setPost($post)
	{
		$this->setCache('post', $post);
		$this->postId = $post->id;
	}

	public function setCommenter($user)
	{
		$this->setCache('commenter', $user);
		$this->commenterId = $user ? $user->id : null;
	}

	public function getPost()
	{
		if ($this->hasCache('post'))
			return $this->getCache('post');
		$post = PostModel::findById($this->postId);
		$this->setCache('post', $post);
		return $post;
	}

	public function getCommenter()
	{
		if ($this->hasCache('commenter'))
			return $this->getCache('commenter');
		$user = UserModel::findById($this->commenterId, false);
		$this->setCache('commenter', $user);
		return $user;
	}
}
