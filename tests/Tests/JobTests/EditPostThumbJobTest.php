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
		$img = imagecreatefromjpeg($post->tryGetWorkingThumbPath());
		$this->assert->areEqual(150, imagesx($img));
		$this->assert->areEqual(150, imagesy($img));
		imagedestroy($img);
	}

	public function testFileDifferentDimensions()
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
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->isTrue($post->hasCustomThumb());
		$img = imagecreatefromjpeg($post->tryGetWorkingThumbPath());
		$this->assert->areEqual(150, imagesx($img));
		$this->assert->areEqual(150, imagesy($img));
		imagedestroy($img);
	}
}
