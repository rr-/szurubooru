<?php
class CommentMocker extends AbstractMocker implements IMocker
{
	protected $postMocker;

	public function __construct(PostMocker $postMocker)
	{
		$this->postMocker = $postMocker;
	}

	public function mockSingle()
	{
		$post = $this->postMocker->mockSingle();
		$comment = CommentModel::spawn();
		$comment->setPost($post);
		$comment->setText('test test');
		return CommentModel::save($comment);
	}
}
