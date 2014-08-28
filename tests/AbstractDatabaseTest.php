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
		$connectingString = sprintf('mongodb://%s:%d/%s', $host, $port, $database);
		$this->connection = new \Mongo($connectingString);
		$this->db = $this->connection->selectDb($database);
		$this->upgradeService = new \Szurubooru\UpgradeService($this->db);
		$this->upgradeService->prepareForUsage();
	}

	public function tearDown()
	{
		$this->upgradeService->removeAllData();
	}
}
