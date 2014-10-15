<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\PublicFileDao;
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
		$this->assertEquals(1, $result[0]->getId());
		$this->assertEquals('test', $result[0]->getName());
	}

	public function testCreatingTagsWhenAllExist()
	{
		$pdo = $this->databaseConnection->getPDO();
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (1, \'test1\', \'2014-10-01 00:00:00\')');
		$pdo->exec('INSERT INTO tags(id, name, creationTime) VALUES (2, \'test2\', \'2014-10-01 00:00:00\')');

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

		$tag1 = new Tag();
		$tag1->setName('test1');
		$tag2 = new Tag();
		$tag2->setName('test3');

		$tagService = $this->getTagService();
		$result = $tagService->createTags([$tag1, $tag2]);
		$this->assertEquals(2, count($result));
		$this->assertEquals(1, $result[0]->getId());
		$this->assertEquals(3, $result[1]->getId());
		$this->assertEquals('test1', $result[0]->getName());
		$this->assertEquals('test3', $result[1]->getName());
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
