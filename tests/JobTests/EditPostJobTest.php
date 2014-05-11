<?php
class EditPostJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->grantAccess('editPost');
		$this->grantAccess('editPostSafety');
		$this->grantAccess('editPostTags');
		$this->grantAccess('editPostSource');
		$this->grantAccess('editPostContent');

		$post = $this->mockPost(Auth::getCurrentUser());

		$args =
		[
			JobArgs::ARG_POST_ID => $post->getId(),
			JobArgs::ARG_NEW_SAFETY => PostSafety::Sketchy,
			JobArgs::ARG_NEW_SOURCE => 'some source huh',
			JobArgs::ARG_NEW_POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
		];

		$this->assert->doesNotThrow(function() use ($args)
		{
			Api::run(new EditPostJob(), $args);
		});
	}

	public function testPrivilegeFail()
	{
		$this->grantAccess('editPost');
		$this->grantAccess('editPostSafety');
		$this->grantAccess('editPostTags');
		$this->grantAccess('editPostContent');

		$post = $this->mockPost(Auth::getCurrentUser());

		$args =
		[
			JobArgs::ARG_POST_ID => $post->getId(),
			JobArgs::ARG_NEW_SAFETY => PostSafety::Safe,
			JobArgs::ARG_NEW_SOURCE => '',
			JobArgs::ARG_NEW_POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
		];

		$this->assert->throws(function() use ($args)
		{
			Api::run(new EditPostJob(), $args);
		}, 'Insufficient privilege');
	}

	public function testLogBuffering()
	{
		$this->testSaving();

		$logPath = Logger::getLogPath();
		$x = file_get_contents($logPath);
		$lines = array_filter(explode("\n", $x));
		$this->assert->areEqual(3, count($lines));
	}
}
