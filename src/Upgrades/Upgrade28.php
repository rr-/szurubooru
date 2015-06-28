<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade28 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $pdo->exec('CREATE INDEX idx_comments_postId ON comments(postId)');
    }
}
