<?php
namespace Szurubooru\Tests;

abstract class AbstractDatabaseTest extends \PHPUnit_Framework_TestCase
{
	protected $databaseConnection;
	protected $upgradeService;

	public function setUp()
	{
		$host = 'localhost';
		$port = 27017;
		$database = 'test';
		$config = new \Szurubooru\Config();
		$config->databaseHost = 'localhost';
		$config->databasePort = 27017;
		$config->databaseName = 'test';
		$this->databaseConnection = new \Szurubooru\DatabaseConnection($config);
		$this->upgradeService = new \Szurubooru\UpgradeService($this->databaseConnection);
		$this->upgradeService->prepareForUsage();
	}

	public function tearDown()
	{
		$this->upgradeService->removeAllData();
	}
}
