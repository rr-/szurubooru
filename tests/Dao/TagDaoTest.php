<?php
namespace Szurubooru\Tests\Dao;

final class TagDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	public function setUp()
	{
		parent::setUp();
	}

	public function testFindByPostIds()
	{
		$pdo = $this->databaseConnection->getPDO();

		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (1, \'test1\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (2, \'test2\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (5, 1)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (6, 1)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (5, 2)');
		$pdo->exec('INSERT INTO postTags(postId, tagId) VALUES (6, 2)');

		$tag1 = new \Szurubooru\Entities\Tag(1);
		$tag1->setName('test1');
		$tag1->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));
		$tag2 = new \Szurubooru\Entities\Tag(2);
		$tag2->setName('test2');
		$tag2->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));

		$expected = [
			$tag1->getId() => $tag1,
			$tag2->getId() => $tag2,
		];
		$tagDao = $this->getTagDao();
		$actual = $tagDao->findByPostId(5);
		$this->assertEntitiesEqual($expected, $actual);
	}

	private function getTagDao()
	{
		return new \Szurubooru\Dao\TagDao($this->databaseConnection);
	}
}
