<?php
class EditPostContentJobTest extends AbstractTest
{
	public function testFile()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.jpg');
		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::getById($post->getId());
		});
		$this->assert->isTrue($post->getFileSize() > 0);
		$this->assert->isNotNull($post->getFileHash());
		$this->assert->isNotNull($post->getMimeType());
		$this->assert->isNotNull($post->getType()->toInteger());
	}

	public function testFileJpeg()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.jpg');
		$this->assert->areEqual('image/jpeg', $post->getMimeType());
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->getImageWidth());
		$this->assert->areEqual(240, $post->getImageHeight());
		$this->assert->doesNotThrow(function() use ($post)
		{
			$post->generateThumb();
		});
	}

	public function testFilePng()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.png');
		$this->assert->areEqual('image/png', $post->getMimeType());
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->getImageWidth());
		$this->assert->areEqual(240, $post->getImageHeight());
		$this->assert->doesNotThrow(function() use ($post)
		{
			$post->generateThumb();
		});
	}

	public function testFileGif()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.gif');
		$this->assert->areEqual('image/gif', $post->getMimeType());
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->getImageWidth());
		$this->assert->areEqual(240, $post->getImageHeight());
		$this->assert->doesNotThrow(function() use ($post)
		{
			$post->generateThumb();
		});
	}

	public function testFileInvalid()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$this->assert->throws(function()
		{
			$this->uploadFromFile('text.txt');
		}, 'Invalid file type');
	}

	public function testUrl()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromUrl('image.jpg');
		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::getById($post->getId());
		});
		$this->assert->isTrue($post->getFileSize() > 0);
		$this->assert->isNotNull($post->getFileHash());
		$this->assert->isNotNull($post->getMimeType());
		$this->assert->isNotNull($post->getType()->toInteger());
	}

	public function testUrlYoutube()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');

		$post = $this->mockPost(Auth::getCurrentUser());
		$post = Api::run(
			new EditPostContentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_POST_CONTENT_URL => 'http://www.youtube.com/watch?v=qWq_jydCUw4',
			]);
		$this->assert->areEqual(PostType::Youtube, $post->getType()->toInteger());
		$this->assert->areEqual('qWq_jydCUw4', $post->getFileHash());
		$this->assert->doesNotThrow(function() use ($post)
		{
			$post->generateThumb();
		});

		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::getById($post->getId());
		});
	}

	public function testWrongPostId()
	{
		$this->assert->throws(function()
		{
			Api::run(
				new EditPostContentJob(),
				[
					JobArgs::ARG_POST_ID => 100,
					JobArgs::ARG_NEW_POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'Invalid post ID');
	}


	public function testDuplicateFile()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.png');
		$this->assert->areEqual('image/png', $post->getMimeType());
		$this->assert->throws(function()
		{
			$this->uploadFromFile('image.png');
		}, 'Duplicate upload: @1');
	}

	public function testDuplicateUrl()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromUrl('image.png');
		$this->assert->areEqual('image/png', $post->getMimeType());
		$this->assert->throws(function()
		{
			$this->uploadFromUrl('image.png');
		}, 'Duplicate upload: @1');
	}

	public function testDuplicateYoutube()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');

		$url = 'http://www.youtube.com/watch?v=qWq_jydCUw4';

		$post = $this->mockPost(Auth::getCurrentUser());
		$post = Api::run(
			new EditPostContentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_POST_CONTENT_URL => $url,
			]);

		$post = $this->mockPost(Auth::getCurrentUser());
		$this->assert->throws(function() use ($post, $url)
		{
			Api::run(
				new EditPostContentJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_POST_CONTENT_URL => $url,
				]);
		}, 'Duplicate upload: @1');
	}

	protected function prepare()
	{
		$this->login($this->mockUser());
	}

	protected function uploadFromUrl($fileName, $post = null)
	{
		if ($post === null)
			$post = $this->mockPost(Auth::getCurrentUser());

		$url = 'http://example.com/mock_' . $fileName;
		TransferHelper::mockForDownload($url, $this->getPath($fileName));

		$post = Api::run(
			new EditPostContentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_POST_CONTENT_URL => $url,
			]);

		$this->assert->isNotNull($post->tryGetWorkingFullPath());
		$this->assert->areEqual(
			file_get_contents($this->getPath($fileName)),
			file_get_contents($post->tryGetWorkingFullPath()));

		return $post;
	}

	protected function uploadFromFile($fileName, $post = null)
	{
		if ($post === null)
			$post = $this->mockPost(Auth::getCurrentUser());

		$post = Api::run(
			new EditPostContentJob(),
			[
				JobArgs::ARG_POST_ID => $post->getId(),
				JobArgs::ARG_NEW_POST_CONTENT => new ApiFileInput($this->getPath($fileName), 'test.jpg'),
			]);

		$this->assert->isNotNull($post->tryGetWorkingFullPath());
		$this->assert->areEqual(
			file_get_contents($this->getPath($fileName)),
			file_get_contents($post->tryGetWorkingFullPath()));

		return $post;
	}
}
