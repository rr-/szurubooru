<?php
namespace Szurubooru\Upgrades;

class Upgrade05 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();

		$pdo->exec('
			CREATE TABLE tags2
			(
				id INTEGER PRIMARY KEY NOT NULL,
				name TEXT UNIQUE NOT NULL,
				usages INTEGER NOT NULL DEFAULT 0
			)');
		$pdo->exec('INSERT INTO tags2(name, usages) SELECT name, (SELECT COUNT(1) FROM postTags WHERE tagName = tags.name) FROM tags');
		$pdo->exec('DROP TABLE tags');
		$pdo->exec('ALTER TABLE tags2 RENAME TO tags');

		$pdo->exec('
			CREATE TABLE postTags2
			(
				postId INTEGER NOT NULL,
				tagId INTEGER NOT NULL,
				PRIMARY KEY (postId, tagId)
			)');
		$pdo->exec('INSERT INTO postTags2(postId, tagId) SELECT postId, (SELECT tags.id FROM tags WHERE tags.name = postTags.tagName) FROM postTags');
		$pdo->exec('DROP TABLE postTags');
		$pdo->exec('ALTER TABLE postTags2 RENAME TO postTags');
	}
}
