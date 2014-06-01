<?php
class EditPostThumbnailJobTest extends AbstractTest
{
	public function testFile()
	{
		$this->grantAccess('editPostThumbnail');
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->hasCustomThumbnail());
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new EditPostThumbnailJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_THUMBNAIL_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('thumb.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->isTrue($post->hasCustomThumbnail());
		$img = imagecreatefromjpeg($post->getThumbnailPath());
		$this->assert->areEqual(150, imagesx($img));
		$this->assert->areEqual(150, imagesy($img));
		imagedestroy($img);
	}

	public function testFileDifferentDimensions()
	{
		$this->grantAccess('editPostThumbnail');
		$post = $this->postMocker->mockSingle();

		$this->assert->isFalse($post->hasCustomThumbnail());
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new EditPostThumbnailJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_THUMBNAIL_CONTENT =>
						new ApiFileInput($this->testSupport->getPath('image.jpg'), 'test.jpg'),
				]);
		});

		$this->assert->isTrue($post->hasCustomThumbnail());
		$img = imagecreatefromjpeg($post->getThumbnailPath());
		$this->assert->areEqual(150, imagesx($img));
		$this->assert->areEqual(150, imagesy($img));
		imagedestroy($img);
	}
}
