<?php
class DeletePostJobTest extends AbstractTest
{
	public function testRemoval()
	{
		$post = $this->postMocker->mockSingle();
		$this->login($this->userMocker->mockSingle());
		$this->grantAccess('deletePost');

		$this->assert->doesNotThrow(function() use ($post)
		{
			Api::run(
				new DeletePostJob(),
				[
					JobArgs::ARG_POST_NAME => $post->getName(),
				]);
		});

		$this->assert->areEqual(null, PostModel::tryGetByName($post->getName()));
		$this->assert->areEqual(0, PostModel::getCount());
	}

	public function testWrongPostId()
	{
		$post = $this->postMocker->mockSingle();
		$this->login($this->userMocker->mockSingle());

		$this->assert->throws(function()
		{
			Api::run(
				new DeletePostJob(),
				[
					JobArgs::ARG_POST_NAME => 'robocop',
				]);
		}, 'Invalid post name');
	}
}

