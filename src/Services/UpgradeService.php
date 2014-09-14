<?php
namespace Szurubooru\Services;

final class UpgradeService
{
	private $config;
	private $upgrades;
	private $databaseConnection;
	private $executedUpgradeNames = [];

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Upgrades\UpgradeRepository $upgradeRepository)
	{
		$this->config = $config;
		$this->databaseConnection = $databaseConnection;
		$this->upgrades = $upgradeRepository->getUpgrades();
		$this->loadExecutedUpgradeNames();
	}

	public function runUpgradesVerbose()
	{
		$this->runUpgrades(true);
	}

	public function runUpgradesQuiet()
	{
		$this->runUpgrades(false);
	}

	private function runUpgrades($verbose)
	{
		foreach ($this->upgrades as $upgrade)
		{
			if ($this->isUpgradeNeeded($upgrade))
			{
				if ($verbose)
					echo 'Running ' . get_class($upgrade) . PHP_EOL;
				$this->runUpgrade($upgrade);
			}
		}
	}

	private function isUpgradeNeeded(\Szurubooru\Upgrades\IUpgrade $upgrade)
	{
		return !in_array(get_class($upgrade), $this->executedUpgradeNames);
	}

	private function runUpgrade(\Szurubooru\Upgrades\IUpgrade $upgrade)
	{
		$upgrade->run($this->databaseConnection);
		$this->executedUpgradeNames[] = get_class($upgrade);
		$this->saveExecutedUpgradeNames();
	}

	private function loadExecutedUpgradeNames()
	{
		$infoFilePath = $this->getExecutedUpgradeNamesFilePath();
		if (!file_exists($infoFilePath))
			return;
		$this->executedUpgradeNames = explode("\n", file_get_contents($infoFilePath));
	}

	private function saveExecutedUpgradeNames()
	{
		$infoFilePath = $this->getExecutedUpgradeNamesFilePath();
		file_put_contents($infoFilePath, implode("\n", $this->executedUpgradeNames));
	}

	private function getExecutedUpgradeNamesFilePath()
	{
		return $this->config->getDataDirectory() . DIRECTORY_SEPARATOR . 'executed_upgrades.txt';
	}
}
