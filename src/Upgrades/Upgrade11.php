<?php
namespace Szurubooru\Upgrades;

class Upgrade11 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('ALTER TABLE posts ADD COLUMN score INTEGER NOT NULL DEFAULT 0');

		$pdo->exec('CREATE TABLE postScores
			(
				id INTEGER PRIMARY KEY NOT NULL,
				userId INTEGER NOT NULL,
				postId INTEGER NOT NULL,
				time TIMESTAMP NOT NULL,
				score INTEGER NOT NULL,
				UNIQUE (userId, postId)
			)');

		$pdo->exec('
			CREATE TRIGGER postScoresDelete AFTER DELETE ON postScores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET score = (
					SELECT SUM(score) FROM postScores
					WHERE postScores.postId = posts.id)
				WHERE posts.id = OLD.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER postScoresInsert AFTER INSERT ON postScores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET score = (
					SELECT SUM(score) FROM postScores
					WHERE postScores.postId = posts.id)
				WHERE posts.id = NEW.postId;
			END');


		$pdo->exec('
			CREATE TRIGGER postScoresUpdate AFTER UPDATE ON postScores
			FOR EACH ROW
			BEGIN
				UPDATE posts SET score = (
					SELECT SUM(score) FROM postScores
					WHERE postScores.postId = posts.id)
				WHERE posts.id = OLD.postId;

				UPDATE posts SET score = (
					SELECT SUM(score) FROM postScores
					WHERE postScores.postId = posts.id)
				WHERE posts.id = NEW.postId;
			END');
	}
}
