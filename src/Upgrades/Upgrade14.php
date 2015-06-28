<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade14 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();
        $driver = $databaseConnection->getDriver();

        $pdo->exec('CREATE TABLE comments
            (
                id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
                postId INTEGER NOT NULL,
                userId INTEGER,
                creationTime DATETIME NOT NULL,
                lastEditTime DATETIME NOT NULL,
                text TEXT
            )');

        $pdo->exec('
            CREATE TRIGGER commentsDelete AFTER DELETE ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id = OLD.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER commentsInsert AFTER INSERT ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id = NEW.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER commentsUpdate AFTER UPDATE ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id IN (OLD.postId, NEW.postId);
            END');

        $pdo->exec('ALTER TABLE posts ADD COLUMN commentCount INTEGER NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE posts ADD COLUMN lastCommentTime DATETIME');
    }
}
