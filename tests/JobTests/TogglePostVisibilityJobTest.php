<?php
class TogglePostVisibilityJobTest extends AbstractTest
{
	public function testHiding()
	{
		$this->grantAccess('hidePost');
		$this->login($this->userMocker->mockSingle());
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->isHidden());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostVisibilityJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 0,
				]);
		});

		$this->assert->isTrue($post->isHidden());
	}

	public function testShowing()
	{
		$this->grantAccess('hidePost');
		$this->login($this->userMocker->mockSingle());
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->isHidden());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostVisibilityJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 0,
				]);
		});

		$this->assert->isTrue($post->isHidden());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostVisibilityJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->assert->isFalse($post->isHidden());
	}
}
