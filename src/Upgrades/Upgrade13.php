<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade13 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('DROP TRIGGER postTagsDelete');
		$pdo->exec('DROP TRIGGER favoritesDelete');
		$pdo->exec('DROP TRIGGER favoritesInsert');
		$pdo->exec('DROP TRIGGER favoritesUpdate');

		$pdo->exec('
			CREATE TRIGGER postTagsDelete AFTER DELETE ON postTags
			FOR EACH ROW
			BEGIN
				UPDATE posts SET tagCount = tagCount - 1 WHERE posts.id = OLD.postId;
				UPDATE tags SET usages = usages - 1 WHERE tags.id = OLD.tagId;
			END');

		$pdo->exec('
			CREATE TRIGGER favoritesDelete AFTER DELETE ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					favCount = (SELECT COUNT(1) FROM favorites WHERE favorites.postId = posts.id),
					lastFavTime = (SELECT MAX(time) FROM favorites WHERE favorites.postId = posts.id)
					WHERE posts.id = OLD.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER favoritesInsert AFTER INSERT ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					favCount = (SELECT COUNT(1) FROM favorites WHERE favorites.postId = posts.id),
					lastFavTime = (SELECT MAX(time) FROM favorites WHERE favorites.postId = posts.id)
					WHERE posts.id = NEW.postId;
			END');

		$pdo->exec('
			CREATE TRIGGER favoritesUpdate AFTER UPDATE ON favorites
			FOR EACH ROW
			BEGIN
				UPDATE posts SET
					favCount = (SELECT COUNT(1) FROM favorites WHERE favorites.postId = posts.id),
					lastFavTime = (SELECT MAX(time) FROM favorites WHERE favorites.postId = posts.id)
					WHERE posts.id IN (OLD.postId, NEW.postId);
			END');

	}
}
