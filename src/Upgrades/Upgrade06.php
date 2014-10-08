<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade06 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('
			CREATE TRIGGER postTagsDelete BEFORE DELETE ON postTags
			FOR EACH ROW
			BEGIN
				UPDATE posts SET tagCount = tagCount - 1 WHERE posts.id = OLD.postId;
				UPDATE tags SET usages = usages - 1 WHERE tags.id = OLD.tagId;
			END');

		$pdo->exec('
			CREATE TRIGGER postTagsInsert AFTER INSERT ON postTags
			FOR EACH ROW
			BEGIN
				UPDATE posts SET tagCount = tagCount + 1 WHERE posts.id = NEW.postId;
				UPDATE tags SET usages = usages + 1 WHERE tags.id = NEW.tagId;
			END');

		$pdo->exec('
			CREATE TRIGGER postTagsUpdate AFTER UPDATE ON postTags
			FOR EACH ROW
			BEGIN
				UPDATE posts SET tagCount = tagCount + 1 WHERE posts.id = NEW.postId;
				UPDATE posts SET tagCount = tagCount - 1 WHERE posts.id = OLD.postId;
				UPDATE tags SET usages = usages + 1 WHERE tags.id = NEW.tagId;
				UPDATE tags SET usages = usages - 1 WHERE tags.id = OLD.tagId;
			END');

		$pdo->exec('ALTER TABLE posts ADD COLUMN tagCount INTEGER NOT NULL DEFAULT 0');
	}
}
