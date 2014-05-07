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
					EditPostTagsJob::TAG_NAMES => ['kamen', 'raider'],
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
					EditPostTagsJob::TAG_NAMES => ['kamen', 'raider'],
				]);
		});

		$this->assert->areEqual(
			file_get_contents($post->getFullPath()),
			file_get_contents($this->getPath('image.jpg')));
		$this->assert->areNotEqual(Auth::getCurrentUser()->getId(), $post->getUploaderId());
		$this->assert->areEqual(null, $post->getUploaderId());
	}

	public function testPartialPrivilegeFail()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostSafety');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$this->assert->throws(function()
		{
			Api::run(
				new AddPostJob(),
				[
					EditPostSafetyJob::SAFETY => PostSafety::Safe,
					EditPostSourceJob::SOURCE => '',
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'Insufficient privilege');
	}

	public function testInvalidSafety()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');
		$this->grantAccess('addPostSafety');

		$this->assert->throws(function()
		{
			Api::run(
				new AddPostJob(),
				[
					EditPostSafetyJob::SAFETY => 666,
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
					EditPostTagsJob::TAG_NAMES => ['kamen', 'raider'],
				]);
		}, 'Invalid safety type');
	}

	public function testNoContentFail()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$this->assert->throws(function()
		{
			Api::run(
				new AddPostJob(),
				[
					EditPostTagsJob::TAG_NAMES => ['kamen', 'raider'],
				]);
		}, 'No post type detected');
	}

	public function testEmptyTagsFail()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$this->assert->throws(function()
		{
			Api::run(
				new AddPostJob(),
				[
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'No tags set');
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
