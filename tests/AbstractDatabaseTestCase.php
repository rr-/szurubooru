<?php
namespace Szurubooru\Tests;

abstract class AbstractDatabaseTestCase extends \Szurubooru\Tests\AbstractTestCase
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
		$this->databaseConnection = new \Szurubooru\DatabaseConnection($config);
		\Szurubooru\Injector::set(\Szurubooru\DatabaseConnection::class, $this->databaseConnection);
		\Szurubooru\Injector::set(\Szurubooru\Services\FileService::class, $fileService);

		$upgradeRepository = \Szurubooru\Injector::get(\Szurubooru\Upgrades\UpgradeRepository::class);
		$upgradeService = new \Szurubooru\Services\UpgradeService($config, $this->databaseConnection, $upgradeRepository);
		$upgradeService->runUpgradesQuiet();
	}

	private function prepareFileService()
	{
		$testDirectory = $this->createTestDirectory();
		$configMock = $this->mockConfig(null, $testDirectory);
		$httpHelper = \Szurubooru\Injector::get(\Szurubooru\Helpers\HttpHelper::class);
		return new \Szurubooru\Services\FileService($configMock, $httpHelper);
	}

	public function tearDown()
	{
		parent::tearDown();
		if ($this->databaseConnection)
			$this->databaseConnection->close();
	}

	protected static function getTestPost()
	{
		$post = new \Szurubooru\Entities\Post();
		$post->setName('test');
		$post->setUploadTime(date('c'));
		$post->setSafety(\Szurubooru\Entities\Post::POST_SAFETY_SAFE);
		$post->setContentType(\Szurubooru\Entities\Post::POST_TYPE_YOUTUBE);
		$post->setContentChecksum('whatever');
		return $post;
	}

	protected static function getTestUser($userName = 'test')
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName($userName);
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime(date('c', mktime(1, 2, 3)));
		$user->setRegistrationTime(date('c', mktime(3, 2, 1)));
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
