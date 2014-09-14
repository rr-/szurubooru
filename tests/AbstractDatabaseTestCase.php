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

		$this->databaseConnection = new \Szurubooru\DatabaseConnection($config);

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
