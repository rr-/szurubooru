<?php
class GetPostJobTest extends AbstractTest
{
	public function testPostRetrieval()
	{
		$this->grantAccess('viewPost');
		$post = $this->postMocker->mockSingle();

		$samePost = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new GetPostJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
				]);
		});

		$post->resetCache();
		$samePost->resetCache();
		$this->assert->areEquivalent($post, $samePost);
	}

	public function testInvalidId()
	{
		$this->grantAccess('viewPost');

		$this->assert->throws(function()
		{
			Api::run(
				new GetPostJob(),
				[
					JobArgs::ARG_POST_ID => 100,
				]);
		}, 'Invalid post ID');
	}
}
