<?php
namespace Szurubooru\Services;
use Szurubooru\Config;
use Szurubooru\DatabaseConnection;
use Szurubooru\Upgrades\IUpgrade;
use Szurubooru\Upgrades\UpgradeRepository;

final class UpgradeService
{
    private $config;
    private $upgrades;
    private $databaseConnection;
    private $executedUpgradeNumbers = [];

    public function __construct(
        Config $config,
        DatabaseConnection $databaseConnection,
        UpgradeRepository $upgradeRepository)
    {
        $this->config = $config;
        $this->databaseConnection = $databaseConnection;
        $this->upgrades = $upgradeRepository->getUpgrades();
        $this->loadExecutedUpgradeNumbers();
    }

    public function runUpgradesVerbose()
    {
        echo $this->config->database->user . '@' . $this->config->database->dsn . PHP_EOL;
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
                    $this->log('Running ' . get_class($upgrade));
                $this->runUpgrade($upgrade);
                if ($verbose)
                    $this->log(PHP_EOL);
            }
        }
    }

    private function isUpgradeNeeded(IUpgrade $upgrade)
    {
        return !in_array($this->getUpgradeNumber($upgrade), $this->executedUpgradeNumbers);
    }

    private function runUpgrade(IUpgrade $upgrade)
    {
        $upgrade->run($this->databaseConnection);
        $number = $this->getUpgradeNumber($upgrade);
        $this->executedUpgradeNumbers[] = $number;
        $this->databaseConnection->getPDO()->insertInto('executedUpgrades')->values(['number' => $number])->execute();
    }

    private function loadExecutedUpgradeNumbers()
    {
        $this->executedUpgradeNumbers = [];
        try
        {
            foreach ($this->databaseConnection->getPDO()->from('executedUpgrades') as $row)
            {
                $this->executedUpgradeNumbers[] = intval($row['number']);
            }
        }
        catch (\Exception $e)
        {
            //most probably, no table found - need to execute all upgrades
        }
    }

    private function getUpgradeNumber(IUpgrade $upgrade)
    {
        $className = get_class($upgrade);
        preg_match('/(\d+)/', $className, $matches);
        return intval($matches[1]);
    }

    private function log($message)
    {
        echo $message;
        if (ob_get_level())
            ob_flush();
        flush();
    }
}
