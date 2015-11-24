<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade41 implements IUpgrade
{
    public function run(DatabaseConnection $databaseConnection)
    {
        $pdo = $databaseConnection->getPDO();

        $pdo->exec('
            CREATE TRIGGER postNotesDelete AFTER DELETE ON postNotes
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    noteCount = (SELECT COUNT(1) FROM postNotes WHERE postNotes.postId = posts.id)
                    WHERE posts.id = OLD.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER postNotesInsert AFTER INSERT ON postNotes
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    noteCount = (SELECT COUNT(1) FROM postNotes WHERE postNotes.postId = posts.id)
                    WHERE posts.id = NEW.postId;
            END');

        $pdo->exec('
            CREATE TRIGGER postNotesUpdate AFTER UPDATE ON postNotes
            FOR EACH ROW
            BEGIN
                UPDATE posts SET
                    noteCount = (SELECT COUNT(1) FROM postNotes WHERE postNotes.postId = posts.id)
                    WHERE posts.id IN (OLD.postId, NEW.postId);
            END');

        $pdo->exec('ALTER TABLE posts ADD COLUMN noteCount INTEGER NOT NULL DEFAULT 0');
        $pdo->exec('UPDATE posts SET noteCount = (SELECT COUNT(1) FROM postNotes WHERE postNotes.postId = posts.id)');
    }
}
