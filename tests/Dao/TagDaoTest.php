<?php
namespace Szurubooru\Tests\Dao;

final class TagDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $fileServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
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

	public function testExportSingle()
	{
		$tag1 = new \Szurubooru\Entities\Tag();
		$tag1->setName('test');
		$tag1->setCreationTime(date('c'));
		$this->fileServiceMock->expects($this->once())->method('save')->with('tags.json', '{"test":0}');
		$tagDao = $this->getTagDao();
		$tagDao->save($tag1);
	}

	public function testExportMultiple()
	{
		$tag1 = new \Szurubooru\Entities\Tag();
		$tag1->setName('test1');
		$tag1->setCreationTime(date('c'));
		$tag2 = new \Szurubooru\Entities\Tag();
		$tag2->setName('test2');
		$tag2->setCreationTime(date('c'));
		$this->fileServiceMock->expects($this->once())->method('save')->with('tags.json', '{"test1":0,"test2":0}');
		$tagDao = $this->getTagDao();
		$tagDao->batchSave([$tag1, $tag2]);
	}

	private function getTagDao()
	{
		return new \Szurubooru\Dao\TagDao($this->databaseConnection, $this->fileServiceMock);
	}
}
