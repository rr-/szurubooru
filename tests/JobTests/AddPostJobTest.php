<?php
class AddPostJobTest extends AbstractTest
{
	public function testSaving()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostSafety');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostSource');
		$this->grantAccess('addPostContent');

		$args =
		[
			AddPostJob::ANONYMOUS => false,
			EditPostSafetyJob::SAFETY => PostSafety::Safe,
			EditPostSourceJob::SOURCE => '',
			EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
		];

		$this->assert->doesNotThrow(function() use ($args)
		{
			Api::run(new AddPostJob(), $args);
		});
	}

	public function testPrivilegeFail()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostSafety');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$args =
		[
			AddPostJob::ANONYMOUS => false,
			EditPostSafetyJob::SAFETY => PostSafety::Safe,
			EditPostSourceJob::SOURCE => '',
			EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
		];

		$this->assert->throws(function() use ($args)
		{
			Api::run(new AddPostJob(), $args);
		}, 'Insufficient privilege');
	}

	public function testLogBuffering()
	{
		$this->testSaving();

		$logPath = Logger::getLogPath();
		$x = file_get_contents($logPath);
		$lines = array_filter(explode("\n", $x));
		$this->assert->areEqual(1, count($lines));
	}

	protected function prepare()
	{
		getConfig()->registration->needEmailForUploading = false;
	}
}
