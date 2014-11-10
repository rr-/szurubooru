<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\Tag;
use Szurubooru\Injector;
use Szurubooru\Services\TagService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TagServiceTest extends AbstractDatabaseTestCase
{
	public function testCreatingEmpty()
	{
		$pdo = $this->databaseConnection->getPDO();
		$tagService = $this->getTagService();
		$result = $tagService->createTags([]);
		$this->assertEquals(0, count($result));
	}

	public function testCreatingTagsWhenNoneExist()
	{
		$pdo = $this->databaseConnection->getPDO();
		$tag = new Tag();
		$tag->setName('test');

		$tagService = $this->getTagService();
		$result = $tagService->createTags([$tag]);
		$this->assertEquals(1, count($result));
		$this->assertNotNull($result[0]->getId());
		$this->assertEquals('test', $result[0]->getName());
	}

	public function testCreatingTagsWhenAllExist()
	{
		$pdo = $this->databaseConnection->getPDO();
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (1, \'test1\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (2, \'test2\', \'2014-10-01 00:00:00\')');
		$pdo->exec('UPDATE sequencer SET lastUsedId = 2 WHERE tableName = \'tags\'');

		$tag1 = new Tag();
		$tag1->setName('test1');
		$tag2 = new Tag();
		$tag2->setName('test2');

		$tagService = $this->getTagService();
		$result = $tagService->createTags([$tag1, $tag2]);
		$this->assertEquals(2, count($result));
		$this->assertEquals(1, $result[0]->getId());
		$this->assertEquals(2, $result[1]->getId());
		$this->assertEquals('test1', $result[0]->getName());
		$this->assertEquals('test2', $result[1]->getName());
	}

	public function testCreatingTagsWhenSomeExist()
	{
		$pdo = $this->databaseConnection->getPDO();
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (1, \'test1\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (2, \'test2\', \'2014-10-01 00:00:00\')');
		$pdo->exec('UPDATE sequencer SET lastUsedId = 2 WHERE tableName = \'tags\'');

		$tag1 = new Tag();
		$tag1->setName('test1');
		$tag2 = new Tag();
		$tag2->setName('test3');

		$tagService = $this->getTagService();
		$result = $tagService->createTags([$tag1, $tag2]);
		$this->assertEquals(2, count($result));
		$this->assertEquals(1, $result[0]->getId());
		$this->assertNotNull($result[1]->getId());
		$this->assertEquals('test1', $result[0]->getName());
		$this->assertEquals('test3', $result[1]->getName());
	}

	public function testExportRelations()
	{
		$fileDao = $this->getPublicFileDao();
		$tagService = $this->getTagService();

		$tag1 = new Tag();
		$tag1->setName('test');
		$tag1->setCreationTime(date('c'));

		$tag2 = new Tag();
		$tag2->setName('test 2');
		$tag3 = new Tag();
		$tag3->setName('test 3');
		$tag4 = new Tag();
		$tag4->setName('test 4');
		$tag5 = new Tag();
		$tag5->setName('test 5');
		$tagService->createTags([$tag2, $tag3, $tag4, $tag5]);

		$tag1->setImpliedTags([$tag2, $tag3]);
		$tag1->setSuggestedTags([$tag4, $tag5]);

		$tagService->createTags([$tag1]);
		$tagService->exportJson();
		$this->assertEquals('[' .
			'{"name":"test 2","usages":0,"banned":false},' .
			'{"name":"test 3","usages":0,"banned":false},' .
			'{"name":"test 4","usages":0,"banned":false},' .
			'{"name":"test 5","usages":0,"banned":false},' .
			'{"name":"test","usages":0,"banned":false,"implications":["test 2","test 3"],"suggestions":["test 4","test 5"]}]',
			$fileDao->load('tags.json'));
	}

	public function testExportSingle()
	{
		$tag1 = new Tag();
		$tag1->setName('test');
		$tag1->setCreationTime(date('c'));
		$fileDao = $this->getPublicFileDao();
		$tagService = $this->getTagService();
		$tagService->createTags([$tag1]);
		$tagService->exportJson();
		$this->assertEquals('[{"name":"test","usages":0,"banned":false}]', $fileDao->load('tags.json'));
	}

	public function testMerging()
	{
		$tag1 = self::getTestTag('test 1');
		$tag2 = self::getTestTag('test 2');
		$tag3 = self::getTestTag('test 3');

		$tagDao = Injector::get(TagDao::class);
		$tagDao->save($tag1);
		$tagDao->save($tag2);
		$tagDao->save($tag3);

		$post1 = self::getTestPost();
		$post2 = self::getTestPost();
		$post3 = self::getTestPost();
		$post1->setTags([$tag1]);
		$post2->setTags([$tag1, $tag3]);
		$post3->setTags([$tag2, $tag3]);

		$postDao = Injector::get(PostDao::class);
		$postDao->save($post1);
		$postDao->save($post2);
		$postDao->save($post3);

		$tagService = $this->getTagService();
		$tagService->mergeTag($tag1, $tag2);

		$this->assertNull($tagDao->findByName($tag1->getName()));
		$this->assertNotNull($tagDao->findByName($tag2->getName()));

		$post1 = $postDao->findById($post1->getId());
		$post2 = $postDao->findById($post2->getId());
		$post3 = $postDao->findById($post3->getId());
		$this->assertEntitiesEqual([$tag2], array_values($post1->getTags()));
		$this->assertEntitiesEqual([$tag2, $tag3], array_values($post2->getTags()));
		$this->assertEntitiesEqual([$tag2, $tag3], array_values($post3->getTags()));
	}

	public function testExportMultiple()
	{
		$tag1 = new Tag();
		$tag1->setName('test1');
		$tag1->setCreationTime(date('c'));
		$tag2 = new Tag();
		$tag2->setName('test2');
		$tag2->setCreationTime(date('c'));
		$tag2->setBanned(true);
		$fileDao = $this->getPublicFileDao();
		$tagService = $this->getTagService();
		$tagService->createTags([$tag1, $tag2]);
		$tagService->exportJson();
		$this->assertEquals('[{"name":"test1","usages":0,"banned":false},{"name":"test2","usages":0,"banned":true}]', $fileDao->load('tags.json'));
	}

	private function getPublicFileDao()
	{
		return Injector::get(PublicFileDao::class);
	}

	private function getTagService()
	{
		return Injector::get(TagService::class);
	}
}
