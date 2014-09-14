<?php
namespace Szurubooru;

final class DatabaseConnection
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

	public function close()
	{
		$this->pdo = null;
	}

	private function createPDO()
	{
		$cwd = getcwd();
		if ($this->config->getDataDirectory())
			chdir($this->config->getDataDirectory());
		$this->pdo = new \PDO($this->config->database->dsn);
		$this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
		chdir($cwd);
	}
}
