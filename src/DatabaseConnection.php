<?php
namespace Szurubooru;

final class DatabaseConnection
{
	private $database;
	private $connection;

	public function __construct(\Szurubooru\Config $config)
	{
		$connectionString = $this->getConnectionString($config);
		$this->connection = new \MongoClient($connectionString);
		$this->database = $this->connection->selectDb($config->database->name);
	}

	public function getConnection()
	{
		return $this->connection;
	}

	public function getDatabase()
	{
		return $this->database;
	}

	private function getConnectionString(\Szurubooru\Config $config)
	{
		return sprintf(
			'mongodb://%s:%d/%s',
			$config->database->host,
			$config->database->port,
			$config->database->name);
	}
}
