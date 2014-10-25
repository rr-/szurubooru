<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\PostNote;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class PostNoteDaoTest extends AbstractDatabaseTestCase
{
	public function testSettingValues()
	{
		$expected = new PostNote();
		$expected->setPostId(5);
		$expected->setLeft(5);
		$expected->setTop(10);
		$expected->setWidth(50);
		$expected->setHeight(50);
		$expected->setText('text');

		$postNoteDao = $this->getPostNoteDao();
		$postNoteDao->save($expected);

		$actual = $postNoteDao->findById($expected->getId());
		$this->assertEntitiesEqual($actual, $expected);
	}

	private function getPostNoteDao()
	{
		return new PostNoteDao($this->databaseConnection);
	}
}

