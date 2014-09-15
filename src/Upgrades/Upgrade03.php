<?php
namespace Szurubooru\Upgrades;

class Upgrade03 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$databaseConnection->getPDO()->exec('DROP TABLE "posts"');

		$databaseConnection->getPDO()->exec('
			CREATE TABLE "posts"
			(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT NOT NULL,
				userId INTEGER,
				uploadTime TIMESTAMP NOT NULL,
				lastEditTime TIMESTAMP,
				safety INTEGER NOT NULL,
				contentType INTEGER NOT NULL,
				contentChecksum TEXT NOT NULL,
				source TEXT,
				imageWidth INTEGER,
				imageHeight INTEGER,
				originalFileSize INTEGER,
				originalFileName TEXT
			)');

		$databaseConnection->getPDO()->exec('
			CREATE TABLE "tags"
			(
				name TEXT PRIMARY KEY NOT NULL
			)');

		$databaseConnection->getPDO()->exec('
			CREATE TABLE "postTags"
			(
				postId INTEGER NOT NULL,
				tagName TEXT NOT NULL,
				PRIMARY KEY (postId, tagName)
			)');
	}
}
