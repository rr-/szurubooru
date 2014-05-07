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

		$this->login($this->mockUser());

		$post = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddPostJob(),
				[
					EditPostSafetyJob::SAFETY => PostSafety::Safe,
					EditPostSourceJob::SOURCE => '',
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->areEqual(
			file_get_contents($post->getFullPath()),
			file_get_contents($this->getPath('image.jpg')));
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $post->getUploaderId());
	}

	public function testAnonymousUploads()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$this->login($this->mockUser());

		$post = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddPostJob(),
				[
					AddPostJob::ANONYMOUS => true,
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->areEqual(
			file_get_contents($post->getFullPath()),
			file_get_contents($this->getPath('image.jpg')));
		$this->assert->areNotEqual(Auth::getCurrentUser()->getId(), $post->getUploaderId());
		$this->assert->areEqual(null, $post->getUploaderId());
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
