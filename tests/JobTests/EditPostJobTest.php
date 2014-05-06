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
			EditPostJob::POST_ID => $post->getId(),
			EditPostSafetyJob::SAFETY => PostSafety::Safe,
			EditPostSourceJob::SOURCE => '',
			EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
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
			EditPostJob::POST_ID => $post->getId(),
			EditPostSafetyJob::SAFETY => PostSafety::Safe,
			EditPostSourceJob::SOURCE => '',
			EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
		];

		$this->assert->throws(function() use ($args)
		{
			Api::run(new EditPostJob(), $args);
		}, 'Insufficient privilege');
	}
}
