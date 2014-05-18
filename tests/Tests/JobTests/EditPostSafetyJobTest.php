<?php
class EditPostSafetyJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->prepare();
		$this->grantAccess('editPostSafety.own');

		$post = $this->postMocker->mockSingle();
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new EditPostSafetyJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_SAFETY => PostSafety::Sketchy,
				]);
		});

		$this->assert->areEqual(PostSafety::Sketchy, $post->getSafety()->toInteger());
		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::getById($post->getId());
		});
	}

	public function testWrongPostId()
	{
		$this->prepare();

		$this->assert->throws(function()
		{
			Api::run(
				new EditPostSafetyJob(),
				[
					JobArgs::ARG_POST_ID => 100,
					JobArgs::ARG_NEW_SAFETY => PostSafety::Sketchy,
				]);
		}, 'Invalid post ID');
	}

	public function testWrongSafety()
	{
		$this->prepare();
		$this->grantAccess('editPostSafety.own');

		$post = $this->postMocker->mockSingle();
		$this->assert->throws(function() use ($post)
		{
			Api::run(
				new EditPostSafetyJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_SAFETY => '',
				]);
		}, 'Invalid safety type');
	}

	protected function prepare()
	{
		$this->login($this->userMocker->mockSingle());
	}
}
