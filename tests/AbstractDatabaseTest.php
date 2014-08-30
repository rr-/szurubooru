<?php
namespace Szurubooru\Tests;

abstract class AbstractDatabaseTest extends \PHPUnit_Framework_TestCase
{
	protected $db;
	protected $connection;
	protected $upgradeService;

	public function setUp()
	{
		$host = 'localhost';
		$port = 27017;
		$database = 'test';
		$this->config = new \Szurubooru\Config();
		$this->config->databaseHost = 'localhost';
		$this->config->databasePort = 27017;
		$this->config->databaseName = 'test';
		$this->upgradeService = new \Szurubooru\UpgradeService($this->config);
		$this->upgradeService->prepareForUsage();
	}

	public function tearDown()
	{
		$this->upgradeService->removeAllData();
	}
}
