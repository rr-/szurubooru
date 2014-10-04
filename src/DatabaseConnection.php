<?php
namespace Szurubooru;

class DatabaseConnection
{
	private $pdo;
	private $config;

	public function __construct(\Szurubooru\Config $config)
	{
		$this->config = $config;
	}

	public function getPDO()
	{
		if (!$this->pdo)
		{
			$this->createPDO();
		}
		return $this->pdo;
	}

	public function getDriver()
	{
		return $this->getPDO()->getAttribute(\PDO::ATTR_DRIVER_NAME);
	}

	public function close()
	{
		$this->pdo = null;
	}

	private function createPDO()
	{
		$cwd = getcwd();
		if ($this->config->getDataDirectory())
			chdir($this->config->getDataDirectory());
		$this->pdo = new PDOEx($this->config->database->dsn, $this->config->database->user,
		$this->config->database->password);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		chdir($cwd);
	}
}
