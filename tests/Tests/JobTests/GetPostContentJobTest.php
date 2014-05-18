<?php
class GetPostContentJobTest extends AbstractTest
{
	public function testPostRetrieval()
	{
		$this->grantAccess('viewPost');
		$post = $this->postMocker->mockSingle();

		$output = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new GetPostContentJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$this->assert->isNotNull($post->tryGetWorkingFullPath());
		$this->assert->areEqual(
			file_get_contents($this->testSupport->getPath('image.jpg')),
			$output->fileContent);
	}

	public function testIdFail()
	{
		$this->grantAccess('viewPost');

		$this->assert->throws(function()
		{
			Api::run(
				new GetPostContentJob(),
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
				new GetPostContentJob(),
				[
					JobArgs::ARG_POST_NAME => 'nonsense',
				]);
		}, 'Invalid post name');
	}
}
