<?php
namespace Szurubooru\Upgrades;

class Upgrade10 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('ALTER TABLE posts ADD COLUMN favCount INTEGER NOT NULL DEFAULT 0');
		$pdo->exec('ALTER TABLE posts ADD COLUMN lastFavTime TIMESTAMP');

		$pdo->exec('CREATE TABLE favorites
			(
				id INTEGER PRIMARY KEY NOT NULL,
				userId INTEGER NOT NULL,
				postId INTEGER NOT NULL,
				time TIMESTAMP NOT NULL,
				UNIQUE (userId, postId)
			)');

		$pdo->exec('
			CREATE TRIGGER favoritesDelete BEFORE DELETE ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET favCount = favCount - 1 WHERE posts.id = OLD.postId;

				UPDATE posts SET lastFavTime = (
					SELECT MAX(time) FROM favorites
					WHERE favorites.postId = posts.id)
					WHERE posts.id = OLD.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER favoritesInsert AFTER INSERT ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET favCount = favCount + 1 WHERE posts.id = NEW.postId;

				UPDATE posts SET lastFavTime = (
					SELECT MAX(time) FROM favorites
					WHERE favorites.postId = posts.id)
					WHERE posts.id = NEW.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER favoritesUpdate AFTER UPDATE ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET favCount = favCount + 1 WHERE posts.id = NEW.postId;
				UPDATE posts SET favCount = favCount - 1 WHERE posts.id = OLD.postId;

				UPDATE posts SET lastFavTime = (
					SELECT MAX(time) FROM favorites
					WHERE favorites.postId = posts.id)
					WHERE posts.id IN (OLD.postId, NEW.postId);
			END');
	}
}
