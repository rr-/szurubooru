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

		$post = $this->postMocker->mockSingle();
		$this->assert->areEqual(1, $post->getRevision());

		$args =
		[
			JobArgs::ARG_POST_ID => $post->getId(),
			JobArgs::ARG_POST_REVISION => $post->getRevision(),
			JobArgs::ARG_NEW_SAFETY => PostSafety::Sketchy,
			JobArgs::ARG_NEW_SOURCE => 'some source huh',
			JobArgs::ARG_NEW_POST_CONTENT =>
				new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
		];

		$post = $this->assert->doesNotThrow(function() use ($args)
		{
			return Api::run(new EditPostJob(), $args);
		});

		$this->assert->areEqual(2, $post->getRevision());
	}

	public function testPartialPrivilegeFail()
	{
		$this->grantAccess('editPost');
		$this->grantAccess('editPostSafety');
		$this->grantAccess('editPostTags');
		$this->grantAccess('editPostContent');

		$post = $this->postMocker->mockSingle();

		$args =
		[
			JobArgs::ARG_POST_ID => $post->getId(),
			JobArgs::ARG_POST_REVISION => $post->getRevision(),
			JobArgs::ARG_NEW_SAFETY => PostSafety::Safe,
			JobArgs::ARG_NEW_SOURCE => 'this should make it fail',
			JobArgs::ARG_NEW_POST_CONTENT =>
				new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
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
		$lines = explode("\n", $x);
		$this->assert->areEqual(3, count($lines));
	}
}
