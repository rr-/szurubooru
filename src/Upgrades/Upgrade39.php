<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade39 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('DROP TRIGGER IF EXISTS postsDelete');

        // this triger is needed due to cascade triggers not working in MySQL.
        // (specifically, postTagsDelete won't get triggered.)
        $pdo->exec('
            CREATE TRIGGER postsDelete AFTER DELETE ON posts
            FOR EACH ROW
            BEGIN
                UPDATE tags SET usages = (SELECT COUNT(1) FROM postTags WHERE tagId = tags.id);
            END');
    }
}
