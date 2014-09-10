<?php
namespace Szurubooru\Tests;

abstract class AbstractDatabaseTestCase extends \Szurubooru\Tests\AbstractTestCase
{
	protected $databaseConnection;
	protected $upgradeService;

	public function setUp()
	{
		$host = 'localhost';
		$port = 27017;
		$database = 'test';
		$config = $this->mockConfig();
		$config->set('database/host', 'localhost');
		$config->set('database/port', '27017');
		$config->set('database/name', 'test');
		$this->databaseConnection = new \Szurubooru\DatabaseConnection($config);
		$this->upgradeService = new \Szurubooru\UpgradeService($this->databaseConnection);
		$this->upgradeService->prepareForUsage();
	}

	public function tearDown()
	{
		$this->upgradeService->removeAllData();
	}
}
