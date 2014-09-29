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

		$fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->databaseConnection = new \Szurubooru\DatabaseConnection($config);
		\Szurubooru\Injector::set(\Szurubooru\DatabaseConnection::class, $this->databaseConnection);
		\Szurubooru\Injector::set(\Szurubooru\Services\FileService::class, $fileServiceMock);

		$upgradeRepository = \Szurubooru\Injector::get(\Szurubooru\Upgrades\UpgradeRepository::class);
		$upgradeService = new \Szurubooru\Services\UpgradeService($config, $this->databaseConnection, $upgradeRepository);
		$upgradeService->runUpgradesQuiet();
	}

	public function tearDown()
	{
		parent::tearDown();
		if ($this->databaseConnection)
			$this->databaseConnection->close();
	}
}
