<?php
class GetPostThumbnailJobTest extends AbstractTest
{
	public function testThumbnailRetrieval()
	{
		$this->grantAccess('viewPost');
		$post = $this->postMocker->mockSingle();

		$output = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new GetPostThumbnailJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$this->assert->isNotNull($post->getContentPath());
		$this->assert->isNotNull($post->getThumbnailPath());
		$this->assert->isTrue(file_exists($post->getContentPath()));
		$this->assert->isTrue(file_exists($post->getThumbnailPath()));
		$this->assert->areEqual('image/jpeg', $output->mimeType);
		$this->assert->areNotEqual(
			file_get_contents(Core::getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg'),
			$output->fileContent);
	}

	public function testIdFail()
	{
		$this->grantAccess('viewPost');

		$this->assert->throws(function()
		{
			Api::run(
				new GetPostThumbnailJob(),
				[
					JobArgs::ARG_POST_ID => 100,
				]);
		}, 'unsatisfied');
	}

	public function testInvalidName()
	{
		$this->grantAccess('viewPost');

		$this->assert->throws(function()
		{
			Api::run(
				new GetPostThumbnailJob(),
				[
					JobArgs::ARG_POST_NAME => 'nonsense',
				]);
		}, 'Invalid post name');
	}
}

