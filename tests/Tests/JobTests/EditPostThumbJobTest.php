<?php
class EditPostThumbJobTest extends AbstractTest
{
	public function testFile()
	{
		$this->grantAccess('editPostThumb');
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->hasCustomThumb());
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new EditPostThumbJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_THUMB_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('thumb.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->isTrue($post->hasCustomThumb());
		$this->assert->isNotNull($post->getThumbCustomPath());
		$this->assert->areEqual($post->getThumbCustomPath(), $post->tryGetWorkingThumbPath());
		$this->assert->areEqual(
			file_get_contents($this->testSupport->getPath('thumb.jpg')),
			file_get_contents($post->tryGetWorkingThumbPath()));
	}

	public function testFileInvalidDimensions()
	{
		$this->grantAccess('editPostThumb');
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->hasCustomThumb());
		$this->assert->throws(function() use ($post)
		{
			return Api::run(
				new EditPostThumbJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_THUMB_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'invalid thumbnail size');

		$this->assert->isFalse($post->hasCustomThumb());
		$this->assert->areNotEqual($post->getThumbCustomPath(), $post->tryGetWorkingThumbPath());
	}
}
