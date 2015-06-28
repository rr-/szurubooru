<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade01 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $driver = $databaseConnection->getDriver();

        $databaseConnection->getPDO()->exec('
            CREATE TABLE users
            (
                id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
                name VARCHAR(50) NOT NULL,
                passwordHash VARCHAR(64) NOT NULL,
                email VARCHAR(200),
                emailUnconfirmed VARCHAR(200),
                accessRank INTEGER NOT NULL,
                browsingSettings VARCHAR(300),
                banned BOOLEAN DEFAULT FALSE,
                registrationTime DATETIME DEFAULT NULL,
                lastLoginTime DATETIME DEFAULT NULL,
                avatarStyle INTEGER DEFAULT 1
            );');

        $databaseConnection->getPDO()->exec('
            CREATE TABLE tokens
            (
                id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
                name VARCHAR(200) NOT NULL,
                purpose INTEGER NOT NULL,
                additionalData VARCHAR(200)
            );');

        $databaseConnection->getPDO()->exec('
            CREATE TABLE posts
            (
                id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
                name VARCHAR(200) NOT NULL
            );');
    }
}
