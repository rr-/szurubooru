<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade16 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('ALTER TABLE scores ADD COLUMN commentId INTEGER');
		$pdo->exec('ALTER TABLE comments ADD COLUMN score INTEGER NOT NULL DEFAULT 0');

		$pdo->exec('DROP TRIGGER IF EXISTS scoresDelete');
		$pdo->exec('DROP TRIGGER IF EXISTS scoresInsert');
		$pdo->exec('DROP TRIGGER IF EXISTS scoresUpdate');

		$pdo->exec('
			CREATE TRIGGER scoresDelete AFTER DELETE ON scores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					score = (SELECT SUM(score) FROM scores WHERE scores.postId = posts.id)
				WHERE posts.id = OLD.postId;

				UPDATE comments SET
					score = (SELECT SUM(score) FROM scores WHERE scores.commentId = comments.id)
				WHERE comments.id = OLD.commentId;
			END');

		$pdo->exec('
			CREATE TRIGGER scoresInsert AFTER INSERT ON scores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					score = (SELECT SUM(score) FROM scores WHERE scores.postId = posts.id)
				WHERE posts.id = NEW.postId;

				UPDATE comments SET
					score = (SELECT SUM(score) FROM scores WHERE scores.commentId = comments.id)
				WHERE comments.id = NEW.commentId;
			END');

		$pdo->exec('
			CREATE TRIGGER scoresUpdate AFTER UPDATE ON scores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					score = (SELECT SUM(score) FROM scores WHERE scores.postId = posts.id)
				WHERE posts.id IN (OLD.postId, NEW.postId);

				UPDATE comments SET
					score = (SELECT SUM(score) FROM scores WHERE scores.commentId = comments.id)
				WHERE comments.id IN (OLD.commentId, NEW.commentId);
			END');
	}
}

