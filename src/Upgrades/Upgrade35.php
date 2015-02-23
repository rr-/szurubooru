<?php
namespace Szurubooru\Upgrades;
use Szurubooru\DatabaseConnection;

class Upgrade35 implements IUpgrade
{
	public function run(DatabaseConnection $databaseConnection)
	{
		$pdo = $databaseConnection->getPDO();
		$this->removeOrphans($pdo);
		$this->addPostForeignKeys($pdo);
		$this->addTagForeignKeys($pdo);
		$this->addUserForeignKeys($pdo);
	}

	private function addPostForeignKeys($pdo)
	{
		$pdo->exec('ALTER TABLE favorites MODIFY postId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE favorites ADD FOREIGN KEY (postId) REFERENCES posts(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE postNotes MODIFY postId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE postNotes ADD FOREIGN KEY (postId) REFERENCES posts(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE postRelations MODIFY post1id INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE postRelations MODIFY post2id INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE postRelations ADD FOREIGN KEY (post1id) REFERENCES posts(id) ON DELETE CASCADE');
		$pdo->exec('ALTER TABLE postRelations ADD FOREIGN KEY (post2id) REFERENCES posts(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE postTags MODIFY postId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE postTags ADD FOREIGN KEY (postId) REFERENCES posts(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE scores MODIFY postId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE scores ADD FOREIGN KEY (postId) REFERENCES posts(id) ON DELETE CASCADE');
	}

	private function addTagForeignKeys($pdo)
	{
		$pdo->exec('ALTER TABLE tagRelations MODIFY tag1id INT(11) UNSIGNED NOT NULL');
		$pdo->exec('ALTER TABLE tagRelations MODIFY tag2id INT(11) UNSIGNED NOT NULL');
		$pdo->exec('ALTER TABLE tagRelations ADD FOREIGN KEY (tag1id) REFERENCES tags(id) ON DELETE CASCADE');
		$pdo->exec('ALTER TABLE tagRelations ADD FOREIGN KEY (tag2id) REFERENCES tags(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE postTags MODIFY tagId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE postTags ADD FOREIGN KEY (tagId) REFERENCES tags(id) ON DELETE CASCADE');
	}

	private function addUserForeignKeys($pdo)
	{
		$pdo->exec('ALTER TABLE comments MODIFY userId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE comments ADD FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL');

		$pdo->exec('ALTER TABLE favorites MODIFY userId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE favorites ADD FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE posts MODIFY userId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE posts ADD FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL');

		$pdo->exec('ALTER TABLE scores MODIFY userId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE scores ADD FOREIGN KEY (userId) REFERENCES users(id) ON DELETE CASCADE');

		$pdo->exec('ALTER TABLE snapshots MODIFY userId INT(11) UNSIGNED');
		$pdo->exec('ALTER TABLE snapshots ADD FOREIGN KEY (userId) REFERENCES users(id) ON DELETE SET NULL');
	}

	private function removeOrphans($pdo)
	{
		$pdo->exec('
			CREATE TEMPORARY TABLE tempTable
				(SELECT postId FROM postTags WHERE
					(SELECT COUNT(1) FROM posts WHERE posts.id = postId) = 0)');
		$pdo->exec(
			'DELETE FROM postTags WHERE EXISTS (SELECT 1 FROM tempTable WHERE tempTable.postId = postTags.postId)');
		$pdo->exec('DROP TABLE tempTable');

		$pdo->exec('
			CREATE TEMPORARY TABLE tempTable
				(SELECT postId FROM scores WHERE
					(SELECT COUNT(1) FROM posts WHERE posts.id = postId) = 0)');
		$pdo->exec(
			'DELETE FROM scores WHERE EXISTS (SELECT 1 FROM tempTable WHERE tempTable.postId = scores.postId)');
		$pdo->exec('DROP TABLE tempTable');

		$pdo->exec('
			DELETE FROM favorites
			WHERE (SELECT COUNT(1) FROM posts WHERE posts.id = postId) = 0');

		$pdo->exec('
			DELETE FROM postNotes
			WHERE (SELECT COUNT(1) FROM posts WHERE posts.id = postId) = 0');

		$pdo->exec('
			DELETE FROM postRelations
			WHERE (SELECT COUNT(1) FROM posts WHERE (posts.id = post1id) OR (posts.id = post2id) = 0)');

		$pdo->exec('
			DELETE FROM tagRelations
			WHERE (SELECT COUNT(1) FROM tags WHERE (tags.id = tag1id) OR (tags.id = tag2id) = 0)');
	}
}
