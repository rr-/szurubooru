<?php
final class CommentEntity extends AbstractEntity implements IValidatable
{
	private $text;
	private $postId;
	private $commentDate;
	private $commenterId;

	public function fillNew()
	{
		$this->commentDate = time();
	}

	public function fillFromDatabase($row)
	{
		$this->id = (int) $row['id'];
		$this->text = $row['text'];
		$this->postId = TextHelper::toIntegerOrNull($row['post_id']);
		$this->commentDate = $row['comment_date'];
		$this->commenterId = TextHelper::toIntegerOrNull($row['commenter_id']);
	}

	public function serializeToArray()
	{
		return
		[
			'text' => $this->getText(),
			'comment-time' => $this->getCreationTime(),
			'commenter' => $this->getCommenter() ? $this->getCommenter()->getName() : null,
		];
	}

	public function validate()
	{
		$config = Core::getConfig();

		if (strlen($this->getText()) < $config->comments->minLength)
			throw new SimpleException('Comment must have at least %d characters', $config->comments->minLength);

		if (strlen($this->getText()) > $config->comments->maxLength)
			throw new SimpleException('Comment must have at most %d characters', $config->comments->maxLength);

		if (!$this->getPostId())
			throw new SimpleException('Trying to save comment that doesn\'t refer to any post');

		if (!$this->getCreationTime())
			throw new SimpleException('Trying to save comment that has no creation date specified');
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
		$this->text = $text === null ? null : trim($text);
	}

	public function getPost()
	{
		if ($this->hasCache('post'))
			return $this->getCache('post');
		$post = PostModel::getById($this->getPostId());
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

	public function getCreationTime()
	{
		return $this->commentDate;
	}

	public function setCreationTime($unixTime)
	{
		$this->commentDate = $unixTime;
	}

	public function getCommenter()
	{
		if ($this->hasCache('commenter'))
			return $this->getCache('commenter');
		if (!$this->commenterId)
			return null;
		$user = UserModel::tryGetById($this->commenterId);
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
