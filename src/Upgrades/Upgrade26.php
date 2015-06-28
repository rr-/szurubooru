<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade26 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $pdo->exec('CREATE INDEX idx_scores_commentId ON scores(commentId)');
        $pdo->exec('CREATE INDEX idx_scores_postId ON scores(postId)');
    }
}
