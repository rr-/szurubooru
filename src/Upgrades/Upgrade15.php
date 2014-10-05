<?php
namespace Szurubooru\Upgrades;

class Upgrade15 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

		$pdo->exec('DROP TABLE IF EXISTS scores');

		$pdo->exec('CREATE TABLE scores
			(
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				userId INTEGER NOT NULL,
				postId INTEGER,
				time DATETIME NOT NULL,
				score INTEGER NOT NULL
			)');

		$pdo->exec('INSERT INTO scores (userId, postId, time, score) SELECT userId, postId, time, score FROM postScores');

		$pdo->exec('DROP TABLE IF EXISTS postScores');
		$pdo->exec('DROP TRIGGER IF EXISTS postScoresDelete');
		$pdo->exec('DROP TRIGGER IF EXISTS postScoresInsert');
		$pdo->exec('DROP TRIGGER IF EXISTS postScoresUpdate');

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
			END');

		$pdo->exec('
			CREATE TRIGGER scoresInsert AFTER INSERT ON scores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					score = (SELECT SUM(score) FROM scores WHERE scores.postId = posts.id)
				WHERE posts.id = NEW.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER scoresUpdate AFTER UPDATE ON scores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					score = (SELECT SUM(score) FROM scores WHERE scores.postId = posts.id)
				WHERE posts.id IN (OLD.postId, NEW.postId);
			END');
	}
}
