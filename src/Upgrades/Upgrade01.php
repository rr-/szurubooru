<?php
namespace Szurubooru\Upgrades;

class Upgrade01 implements IUpgrade
{
	public function run(\Szurubooru\DatabaseConnection $databaseConnection)
	{
		$databaseConnection->getPDO()->exec('
			CREATE TABLE "users"
			(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT NOT NULL,
				passwordHash TEXT NOT NULL,
				email TEXT,
				emailUnconfirmed TEXT,
				accessRank INTEGER NOT NULL,
				browsingSettings TEXT,
				banned INTEGER,
				registrationTime INTEGER DEFAULT NULL,
				lastLoginTime INTEGER DEFAULT NULL,
				avatarStyle INTEGER DEFAULT 1
			);');

		$databaseConnection->getPDO()->exec('
			CREATE TABLE "tokens"
			(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT NOT NULL,
				purpose INTEGER NOT NULL,
				additionalData TEXT
			);');

		$databaseConnection->getPDO()->exec('
			CREATE TABLE "posts"
			(
				id INTEGER PRIMARY KEY AUTOINCREMENT,
				name TEXT NOT NULL
			);');
	}
}
