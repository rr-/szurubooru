<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\GlobalParamDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Snapshot;
use Szurubooru\Entities\User;
use Szurubooru\FormData\UploadFormData;
use Szurubooru\Injector;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PostHistoryService;
use Szurubooru\Services\ImageConverter;
use Szurubooru\Services\ImageManipulation\ImageManipulator;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\PostService;
use Szurubooru\Services\TagService;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractDatabaseTestCase;
use Szurubooru\Validator;

final class PostServiceTest extends AbstractDatabaseTestCase
{
	private $configMock;
	private $validatorMock;
	private $transactionManagerMock;
	private $postDaoMock;
	private $globalParamDaoMock;
	private $authServiceMock;
	private $timeServiceMock;
	private $networkingServiceMock;
	private $tagService;
	private $postHistoryServiceMock;
	private $imageConverterMock;
	private $imageManipulatorMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
		$this->validatorMock = $this->mock(Validator::class);
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->postDaoMock = $this->mock(PostDao::class);
		$this->globalParamDaoMock = $this->mock(GlobalParamDao::class);
		$this->authServiceMock = $this->mock(AuthService::class);
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->networkingServiceMock = $this->mock(NetworkingService::class);
		$this->tagService = Injector::get(TagService::class);
		$this->postHistoryServiceMock = $this->mock(PostHistoryService::class);
		$this->configMock->set('database/maxPostSize', 1000000);
		$this->imageConverterMock = $this->mock(ImageConverter::class);
		$this->imageManipulatorMock = $this->mock(ImageManipulator::class);
	}

	public function testCreatingYoutubePost()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->source = 'source';
		$formData->tags = ['test', 'test2'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->authServiceMock->expects($this->once())->method('getLoggedInUser')->willReturn(new User(5));
		$this->postHistoryServiceMock->expects($this->once())->method('savePostCreation')->willReturn(new Snapshot());
		$this->imageConverterMock->expects($this->never())->method('createImageFromBuffer');

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(Post::POST_SAFETY_SAFE, $savedPost->getSafety());
		$this->assertEquals(5, $savedPost->getUserId());
		$this->assertEquals(Post::POST_TYPE_YOUTUBE, $savedPost->getContentType());
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
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('image.jpg');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->imageManipulatorMock->expects($this->once())->method('getImageWidth')->willReturn(640);
		$this->imageManipulatorMock->expects($this->once())->method('getImageHeight')->willReturn(480);
		$this->postHistoryServiceMock->expects($this->once())->method('savePostCreation')->willReturn(new Snapshot());

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(Post::POST_TYPE_IMAGE, $savedPost->getContentType());
		$this->assertEquals('24216edd12328de3a3c55e2f98220ee7613e3be1', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(687645, $savedPost->getOriginalFileSize());
		$this->assertEquals(640, $savedPost->getImageWidth());
		$this->assertEquals(480, $savedPost->getImageHeight());
	}

	public function testCreatingVideos()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('video.mp4');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->postHistoryServiceMock->expects($this->once())->method('savePostCreation')->willReturn(new Snapshot());
		$this->imageConverterMock->expects($this->once())->method('createImageFromBuffer');

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(Post::POST_TYPE_VIDEO, $savedPost->getContentType());
		$this->assertEquals('16dafaa07cda194d03d590529c06c6ec1a5b80b0', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(14667, $savedPost->getOriginalFileSize());
	}

	public function testCreatingFlashes()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('flash.swf');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->postHistoryServiceMock->expects($this->once())->method('savePostCreation')->willReturn(new Snapshot());
		$this->imageConverterMock->expects($this->once())->method('createImageFromBuffer');

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertEquals(Post::POST_TYPE_FLASH, $savedPost->getContentType());
		$this->assertEquals('d897e044b801d892291b440534c3be3739034f68', $savedPost->getContentChecksum());
		$this->assertEquals($formData->contentFileName, $savedPost->getOriginalFileName());
		$this->assertEquals(226172, $savedPost->getOriginalFileSize());
	}

	public function testFileDuplicates()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = $this->getTestFile('flash.swf');
		$formData->contentFileName = 'blah';

		$this->postDaoMock->expects($this->once())->method('findByContentChecksum')->willReturn(new Post(5));
		$this->setExpectedException(\Exception::class, 'Duplicate post: @5');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testYoutubeDuplicates()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';

		$this->postDaoMock->expects($this->once())->method('findByContentChecksum')->with('QYK2c4OVG6s')->willReturn(new Post(5));
		$this->setExpectedException(\Exception::class, 'Duplicate post: @5');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testTooBigUpload()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->content = 'aa';

		$this->configMock->set('database/maxPostSize', 1);
		$this->setExpectedException(\Exception::class, 'Upload is too big');

		$this->postService = $this->getPostService();
		$this->postService->createPost($formData);
	}

	public function testAnonymousUploads()
	{
		$formData = new UploadFormData;
		$formData->safety = Post::POST_SAFETY_SAFE;
		$formData->tags = ['test'];
		$formData->url = 'https://www.youtube.com/watch?v=QYK2c4OVG6s';
		$formData->anonymous = true;

		$this->postDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->authServiceMock->expects($this->never())->method('getLoggedInUser');
		$this->postHistoryServiceMock->expects($this->once())->method('savePostCreation')->willReturn(new Snapshot());

		$this->postService = $this->getPostService();
		$savedPost = $this->postService->createPost($formData);
		$this->assertNull($savedPost->getUserId());
	}

	private function getPostService()
	{
		return new PostService(
			$this->configMock,
			$this->validatorMock,
			$this->transactionManagerMock,
			$this->postDaoMock,
			$this->globalParamDaoMock,
			$this->authServiceMock,
			$this->timeServiceMock,
			$this->networkingServiceMock,
			$this->tagService,
			$this->postHistoryServiceMock,
			$this->imageConverterMock,
			$this->imageManipulatorMock);
	}
}
