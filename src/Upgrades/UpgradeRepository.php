<?php
namespace Szurubooru\Upgrades;

class UpgradeRepository
{
    private $upgrades = [];

    public function __construct(array $upgrades)
    {
        $this->upgrades = $upgrades;
    }

    public function getUpgrades()
    {
        return $this->upgrades;
    }
}
