<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade42 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('ALTER TABLE posts CHANGE COLUMN lastCommentTime lastCommentEditTime TIMESTAMP NOT NULL DEFAULT 0');
        $pdo->exec('ALTER TABLE posts ADD COLUMN lastCommentCreationTime TIMESTAMP NOT NULL DEFAULT 0');

        $pdo->exec('DROP TRIGGER IF EXISTS commentsDelete');
        $pdo->exec('DROP TRIGGER IF EXISTS commentsInsert');
        $pdo->exec('DROP TRIGGER IF EXISTS commentsUpdate');

        $pdo->exec('
            CREATE TRIGGER commentsDelete AFTER DELETE ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentCreationTime = (SELECT MAX(creationTime) FROM comments WHERE comments.postId = posts.id),
                    lastCommentEditTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id = OLD.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER commentsInsert AFTER INSERT ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentCreationTime = (SELECT MAX(creationTime) FROM comments WHERE comments.postId = posts.id),
                    lastCommentEditTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id = NEW.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER commentsUpdate AFTER UPDATE ON comments
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    commentCount = (SELECT COUNT(1) FROM comments WHERE comments.postId = posts.id),
                    lastCommentCreationTime = (SELECT MAX(creationTime) FROM comments WHERE comments.postId = posts.id),
                    lastCommentEditTime = (SELECT MAX(lastEditTime) FROM comments WHERE comments.postId = posts.id)
                    WHERE posts.id IN (OLD.postId, NEW.postId);
            END');

        $pdo->exec('UPDATE posts SET lastCommentCreationTime = (SELECT MAX(creationTime) FROM comments WHERE comments.postId = posts.id)');
    }
}
