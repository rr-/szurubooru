<?php
namespace Szurubooru\Tests\Services;

class PostServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $validatorMock;
	private $transactionManagerMock;
	private $postDaoMock;
	private $globalParamDaoMock;
	private $authServiceMock;
	private $timeServiceMock;
	private $fileServiceMock;
	private $imageManipulatorMock;

	public function setUp()
	{
		$this->configMock = $this->mockConfig();
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->postDaoMock = $this->mock(\Szurubooru\Dao\PostDao::class);
		$this->globalParamDaoMock = $this->mock(\Szurubooru\Dao\GlobalParamDao::class);
		$this->authServiceMock = $this->mock(\Szurubooru\Services\AuthService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->configMock->set('database/maxPostSize', 1000000);
		$this->imageManipulatorMock = $this->mock(\Szurubooru\Services\ImageManipulation\ImageManipulator::class);
	}

	public function testCreatingYoutubePost()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->source = 'source';
		$formData->tags = ['test', 'test2'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->authServiceMock->expects($this->once())->method('getLoggedInUser')->willReturn(new \Szurubooru\Entities\User(5));

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(\Szurubooru\Entities\Post::POST_SAFETY_SAFE, $savedPost->getSafety());
		$this->assertEquals(5, $savedPost->getUserId());
		$this->assertEquals(\Szurubooru\Entities\Post::POST_TYPE_YOUTUBE, $savedPost->getContentType());
		$this->assertEquals('QYK2c4OVG6s', $savedPost->getContentChecksum());
		$this->assertEquals('source', $savedPost->getSource());
		$this->assertNull($savedPost->getImageWidth());
		$this->assertNull($savedPost->getImageHeight());
		$this->assertEquals($formData->url, $savedPost->getOriginalFileName());
		$this->assertNull($savedPost->getOriginalFileSize());
		$this->assertEquals(2, count($savedPost->getTags()));
		$this->assertEquals('test', $savedPost->getTags()[0]->getName());
		$this->assertEquals('test2', $savedPost->getTags()[1]->getName());
	}

	public function testCreatingPosts()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('image.jpg');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->imageManipulatorMock->expects($this->once())->method('getImageWidth')->willReturn(640);
		$this->imageManipulatorMock->expects($this->once())->method('getImageHeight')->willReturn(480);

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(\Szurubooru\Entities\Post::POST_TYPE_IMAGE, $savedPost->getContentType());
		$this->assertEquals('24216edd12328de3a3c55e2f98220ee7613e3be1', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(687645, $savedPost->getOriginalFileSize());
		$this->assertEquals(640, $savedPost->getImageWidth());
		$this->assertEquals(480, $savedPost->getImageHeight());
	}

	public function testCreatingVideos()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('video.mp4');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(\Szurubooru\Entities\Post::POST_TYPE_VIDEO, $savedPost->getContentType());
		$this->assertEquals('16dafaa07cda194d03d590529c06c6ec1a5b80b0', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(14667, $savedPost->getOriginalFileSize());
	}

	public function testCreatingFlashes()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('flash.swf');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(\Szurubooru\Entities\Post::POST_TYPE_FLASH, $savedPost->getContentType());
		$this->assertEquals('d897e044b801d892291b440534c3be3739034f68', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(226172, $savedPost->getOriginalFileSize());
	}

	public function testFileDuplicates()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('flash.swf');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('findByContentChecksum')->willReturn(new \Szurubooru\Entities\Post(5));
		$this->setExpectedException(\Exception::class, 'Duplicate post: @5');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testYoutubeDuplicates()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';

		$this->postDaoMock->expects($this->once())->method('findByContentChecksum')->with('QYK2c4OVG6s')->willReturn(new \Szurubooru\Entities\Post(5));
		$this->setExpectedException(\Exception::class, 'Duplicate post: @5');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testTooBigUpload()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = 'aa';

		$this->configMock->set('database/maxPostSize', 1);
		$this->setExpectedException(\Exception::class, 'Upload is too big');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testAnonymousUploads()
	{
		$formData = new \Szurubooru\FormData\UploadFormData;
		$formData->safety = \Szurubooru\Entities\Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';
		$formData->anonymous = true;

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->authServiceMock->expects($this->never())->method('getLoggedInUser');

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertNull($savedPost->getUserId());
	}

	private function getPostService()
	{
		return new \Szurubooru\Services\PostService(
			$this->configMock,
			$this->validatorMock,
			$this->transactionManagerMock,
			$this->postDaoMock,
			$this->globalParamDaoMock,
			$this->authServiceMock,
			$this->timeServiceMock,
			$this->fileServiceMock,
			$this->imageManipulatorMock);
	}
}
