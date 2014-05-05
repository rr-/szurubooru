<?php
final class CommentEntity extends AbstractEntity implements IValidatable
{
	protected $text;
	protected $postId;
	protected $commentDate;
	protected $commenterId;

	public function validate()
	{
		$text = trim($this->getText());
		$config = getConfig();

		if (strlen($text) < $config->comments->minLength)
			throw new SimpleException('Comment must have at least %d characters', $config->comments->minLength);

		if (strlen($text) > $config->comments->maxLength)
			throw new SimpleException('Comment must have at most %d characters', $config->comments->maxLength);

		if (!$this->getPostId())
			throw new SimpleException('Trying to save comment that doesn\'t refer to any post');

		if (!$this->getDateTime())
			throw new SimpleException('Trying to save comment that has no creation date specified');

		$this->setText($text);
	}

	public function getText()
	{
		return $this->text;
	}

	public function getTextMarkdown()
	{
		return TextHelper::parseMarkdown($this->getText());
	}

	public function setText($text)
	{
		$this->text = $text;
	}

	public function getPost()
	{
		if ($this->hasCache('post'))
			return $this->getCache('post');
		$post = PostModel::findById($this->getPostId());
		$this->setCache('post', $post);
		return $post;
	}

	public function getPostId()
	{
		return $this->postId;
	}

	public function setPost($post)
	{
		$this->setCache('post', $post);
		$this->postId = $post->getId();
	}

	public function getDateTime()
	{
		return $this->commentDate;
	}

	public function setDateTime($dateTime)
	{
		$this->commentDate = $dateTime;
	}

	public function getCommenter()
	{
		if ($this->hasCache('commenter'))
			return $this->getCache('commenter');
		$user = UserModel::findById($this->getCommenterId(), false);
		$this->setCache('commenter', $user);
		return $user;
	}

	public function getCommenterId()
	{
		return $this->commenterId;
	}

	public function setCommenter($user)
	{
		$this->setCache('commenter', $user);
		$this->commenterId = $user ? $user->getId() : null;
	}
}
