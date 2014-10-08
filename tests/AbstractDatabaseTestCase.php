<?php
namespace Szurubooru\Tests;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Injector;
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

		$this->databaseConnection = new DatabaseConnection($config);
		Injector::set(DatabaseConnection::class, $this->databaseConnection);
		Injector::set(PublicFileDao::class, $this->preparePublicFileDao());

		$upgradeRepository = Injector::get(UpgradeRepository::class);
		$upgradeService = new UpgradeService($config, $this->databaseConnection, $upgradeRepository);
		$upgradeService->runUpgradesQuiet();
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

	private function preparePublicFileDao()
	{
		$testDirectory = $this->createTestDirectory();
		$configMock = $this->mockConfig(null, $testDirectory);
		return new PublicFileDao($configMock);
	}
}
