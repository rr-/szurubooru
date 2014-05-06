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
			PostModel::findById($post->getId());
		});
	}

	public function testFileJpeg()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		$post = $this->uploadFromFile('image.jpg');
		$this->assert->areEqual('image/jpeg', $post->mimeType);
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->imageWidth);
		$this->assert->areEqual(240, $post->imageHeight);
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
		$this->assert->areEqual('image/png', $post->mimeType);
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->imageWidth);
		$this->assert->areEqual(240, $post->imageHeight);
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
		$this->assert->areEqual('image/gif', $post->mimeType);
		$this->assert->areEqual(PostType::Image, $post->getType()->toInteger());
		$this->assert->areEqual(320, $post->imageWidth);
		$this->assert->areEqual(240, $post->imageHeight);
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
			PostModel::findById($post->getId());
		});
	}

	public function testUrlYoutube()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');

		$post = $this->mockPost(Auth::getCurrentUser());
		$post = Api::run(
			new EditPostContentJob(),
			[
				EditPostContentJob::POST_ID => $post->getId(),
				EditPostContentJob::POST_CONTENT_URL => 'http://www.youtube.com/watch?v=qWq_jydCUw4', 'test.jpg',
			]);
		$this->assert->areEqual(PostType::Youtube, $post->getType()->toInteger());
		$this->assert->areEqual('qWq_jydCUw4', $post->fileHash);
		$this->assert->doesNotThrow(function() use ($post)
		{
			$post->generateThumb();
		});

		$this->assert->doesNotThrow(function() use ($post)
		{
			PostModel::findById($post->getId());
		});
	}

	public function testNoAuth()
	{
		$this->prepare();
		$this->grantAccess('editPostContent');
		Auth::setCurrentUser(null);

		$this->assert->doesNotThrow(function()
		{
			$this->uploadFromFile('image.jpg');
		});
	}

	public function testOwnAccessDenial()
	{
		$this->prepare();

		$this->assert->throws(function()
		{
			$this->uploadFromFile('image.jpg');
		}, 'Insufficient privileges');
	}

	public function testOtherAccessGrant()
	{
		$this->prepare();
		$this->grantAccess('editPostContent.all');

		$post = $this->mockPost(Auth::getCurrentUser());

		//login as someone else
		$this->login($this->mockUser());

		$this->assert->doesNotThrow(function() use ($post)
		{
			$this->uploadFromFile('image.jpg', $post);
		});
	}

	public function testOtherAccessDenial()
	{
		$this->prepare();
		$this->grantAccess('editPostContent.own');

		$post = $this->mockPost(Auth::getCurrentUser());

		//login as someone else
		$this->login($this->mockUser());

		$this->assert->throws(function() use ($post)
		{
			$this->uploadFromFile('image.jpg', $post);
		}, 'Insufficient privileges');
	}


	public function testWrongPostId()
	{
		$this->assert->throws(function()
		{
			Api::run(
				new EditPostContentJob(),
				[
					EditPostContentJob::POST_ID => 100,
					EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath('image.jpg'), 'test.jpg'),
				]);
		}, 'Invalid post ID');
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
				EditPostContentJob::POST_ID => $post->getId(),
				EditPostContentJob::POST_CONTENT_URL => $url,
			]);

		$this->assert->areEqual(
			file_get_contents($this->getPath($fileName)),
			file_get_contents(getConfig()->main->filesPath . DS . $post->getName()));

		return $post;
	}

	protected function uploadFromFile($fileName, $post = null)
	{
		if ($post === null)
			$post = $this->mockPost(Auth::getCurrentUser());

		$post = Api::run(
			new EditPostContentJob(),
			[
				EditPostContentJob::POST_ID => $post->getId(),
				EditPostContentJob::POST_CONTENT => new ApiFileInput($this->getPath($fileName), 'test.jpg'),
			]);

		$this->assert->areEqual(
			file_get_contents($this->getPath($fileName)),
			file_get_contents(getConfig()->main->filesPath . DS . $post->getName()));

		return $post;
	}

	protected function getPath($name)
	{
		return getConfig()->rootDir . DS . 'tests' . DS . 'TestFiles' . DS . $name;
	}
}
