<?php
namespace Szurubooru\Tests;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Injector;
use Szurubooru\Services\FileService;
use Szurubooru\Services\UpgradeService;
use Szurubooru\Tests\AbstractTestCase;
use Szurubooru\Upgrades\UpgradeRepository;

abstract class AbstractDatabaseTestCase extends AbstractTestCase
{
	protected $databaseConnection;

	public function setUp()
	{
		parent::setUp();
		$config = $this->mockConfig($this->createTestDirectory());
		$config->set('database/dsn', 'sqlite::memory:');
		$config->set('database/user', '');
		$config->set('database/password', '');

		$fileService = $this->prepareFileService();
		$this->databaseConnection = new DatabaseConnection($config);
		Injector::set(DatabaseConnection::class, $this->databaseConnection);
		Injector::set(FileService::class, $fileService);

		$upgradeRepository = Injector::get(UpgradeRepository::class);
		$upgradeService = new UpgradeService($config, $this->databaseConnection, $upgradeRepository);
		$upgradeService->runUpgradesQuiet();
	}

	private function prepareFileService()
	{
		$testDirectory = $this->createTestDirectory();
		$configMock = $this->mockConfig(null, $testDirectory);
		$httpHelper = Injector::get(HttpHelper::class);
		return new FileService($configMock, $httpHelper);
	}

	public function tearDown()
	{
		parent::tearDown();
		if ($this->databaseConnection)
			$this->databaseConnection->close();
	}

	protected static function getTestPost()
	{
		$post = new Post();
		$post->setName('test');
		$post->setUploadTime(date('c'));
		$post->setSafety(Post::POST_SAFETY_SAFE);
		$post->setContentType(Post::POST_TYPE_YOUTUBE);
		$post->setContentChecksum('whatever');
		return $post;
	}

	protected static function getTestUser($userName = 'test')
	{
		$user = new User();
		$user->setName($userName);
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime(date('c', mktime(1, 2, 3)));
		$user->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user->setAccessRank(User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
