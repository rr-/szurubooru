<?php
class GetPostThumbJobTest extends AbstractTest
{
	public function testThumbRetrieval()
	{
		$this->grantAccess('viewPost');
		$post = $this->postMocker->mockSingle();

		$output = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new GetPostThumbJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$this->assert->isNotNull($post->tryGetWorkingFullPath());
		$this->assert->areEqual('image/jpeg', $output->mimeType);
		$this->assert->areNotEqual(
			file_get_contents(getConfig()->main->mediaPath . DS . 'img' . DS . 'thumb.jpg'),
			$output->fileContent);
	}

	public function testIdFail()
	{
		$this->grantAccess('viewPost');

		$this->assert->throws(function()
		{
			Api::run(
				new GetPostThumbJob(),
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
				new GetPostThumbJob(),
				[
					JobArgs::ARG_POST_NAME => 'nonsense',
				]);
		}, 'Invalid post name');
	}
}

