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

		$this->login($this->userMocker->mockSingle());

		$post = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddPostJob(),
				[
					JobArgs::ARG_ANONYMOUS => '0',
					JobArgs::ARG_NEW_SAFETY => PostSafety::Safe,
					JobArgs::ARG_NEW_SOURCE => '',
					JobArgs::ARG_NEW_TAG_NAMES => ['kamen', 'raider'],
					JobArgs::ARG_NEW_POST_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->areEqual(
			file_get_contents($post->getFullPath()),
			file_get_contents($this->testSupport->getPath('image.jpg')));
		$this->assert->areEqual(Auth::getCurrentUser()->getId(), $post->getUploaderId());
		$this->assert->isNotNull($post->getUploaderId());
	}

	public function testAnonymousUploads()
	{
		$this->prepare();

		$this->grantAccess('addPost');
		$this->grantAccess('addPostTags');
		$this->grantAccess('addPostContent');

		$this->login($this->userMocker->mockSingle());

		$post = $this->assert->doesNotThrow(function()
		{
			return Api::run(
				new AddPostJob(),
				[
					JobArgs::ARG_ANONYMOUS => '1',
					JobArgs::ARG_NEW_TAG_NAMES => ['kamen', 'raider'],
					JobArgs::ARG_NEW_POST_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->areEqual(
			file_get_contents($post->getFullPath()),
			file_get_contents($this->testSupport->getPath('image.jpg')));
		$this->assert->areNotEqual(Auth::getCurrentUser()->getId(), $post->getUploaderId());
		$this->assert->isNull($post->getUploaderId());
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
					JobArgs::ARG_NEW_SAFETY => PostSafety::Safe,
					JobArgs::ARG_NEW_SOURCE => 'this should make it fail',
					JobArgs::ARG_NEW_POST_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
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
					JobArgs::ARG_NEW_SAFETY => 666,
					JobArgs::ARG_NEW_TAG_NAMES => ['kamen', 'raider'],
					JobArgs::ARG_NEW_POST_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
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
					JobArgs::ARG_TAG_NAMES => ['kamen', 'raider'],
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
					JobArgs::ARG_NEW_POST_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'No tags set');
	}

	public function testLogBuffering()
	{
		$this->testSaving();

		$logPath = Logger::getLogPath();
		$x = file_get_contents($logPath);
		$lines = explode("\n", $x);
		$this->assert->areEqual(1, count($lines));
	}

	protected function prepare()
	{
		Core::getConfig()->registration->needEmailForUploading = false;
	}
}
