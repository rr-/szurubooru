<?php
namespace Szurubooru\Upgrades;
use Szurubooru\Dao\UserDao;
use Szurubooru\DatabaseConnection;

class Upgrade40 implements IUpgrade
{
    private $userDao;

    public function __construct(UserDao $userDao)
    {
        $this->userDao = $userDao;
    }

    public function run(DatabaseConnection $databaseConnection)
    {
        foreach ($this->userDao->findAll() as $user)
        {
            $browsingSettings = $user->getBrowsingSettings();
            if ($browsingSettings === null)
                $browsingSettings = new \StdClass;
            $browsingSettings->fitMode = 'fit-width';
            $user->setBrowsingSettings($browsingSettings);
            $this->userDao->save($user);
        }
    }
}
