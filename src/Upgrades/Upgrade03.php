<?php
namespace Szurubooru\Upgrades;

class Upgrade03 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$driver = $databaseConnection->getDriver();

		$pdo->exec('DROP TABLE IF EXISTS posts');

		$pdo->exec('
			CREATE TABLE posts
			(
				id INTEGER PRIMARY KEY ' . ($driver === 'mysql' ? 'AUTO_INCREMENT' : 'AUTOINCREMENT') . ',
				name VARCHAR(40) NOT NULL,
				userId INTEGER,
				uploadTime DATETIME NOT NULL,
				lastEditTime DATETIME,
				safety INTEGER NOT NULL,
				contentType INTEGER NOT NULL,
				contentChecksum VARCHAR(64) NOT NULL,
				source VARCHAR(200),
				imageWidth INTEGER,
				imageHeight INTEGER,
				originalFileSize INTEGER,
				originalFileName VARCHAR(200)
			)');

		$pdo->exec('
			CREATE TABLE tags
			(
				name VARCHAR(64) PRIMARY KEY NOT NULL
			)');

		$pdo->exec('
			CREATE TABLE postTags
			(
				postId INTEGER NOT NULL,
				tagName VARCHAR(64) NOT NULL,
				PRIMARY KEY (postId, tagName)
			)');
	}
}
