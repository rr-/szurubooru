<?php
class PostMocker extends AbstractMocker implements IMocker
{
	private $tagMocker;
	private $testSupport;

	public function __construct(
		TagMocker $tagMocker,
		TestSupport $testSupport)
	{
		$this->testSupport = $testSupport;
		$this->tagMocker = $tagMocker;
	}

	public function mockSingle()
	{
		$post = PostModel::spawn();
		#$post->setUploader($owner);
		$post->setType(new PostType(PostType::Image));
		$post->setTags([$this->tagMocker->mockSingle()]);
		copy($this->testSupport->getPath('image.jpg'), $post->getFullPath());
		return PostModel::save($post);
	}
}
