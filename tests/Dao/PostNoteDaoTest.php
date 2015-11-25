<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\PostNoteDao;
use Szurubooru\Entities\PostNote;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class PostNoteDaoTest extends AbstractDatabaseTestCase
{
    public function testSettingValues()
    {
        $postDao = Injector::get(PostDao::class);
        $postNoteDao = Injector::get(PostNoteDao::class);

        $post = self::getTestPost();
        $postDao->save($post);

        $expected = new PostNote();
        $expected->setPost($post);
        $expected->setLeft(5);
        $expected->setTop(10);
        $expected->setWidth(50);
        $expected->setHeight(50);
        $expected->setText('text');

        $postNoteDao->save($expected);

        $actual = $postNoteDao->findById($expected->getId());
        $this->assertEntitiesEqual($actual, $expected);
        $this->assertNotNull($actual->getPostId());
        $this->assertEntitiesEqual($post, $actual->getPost());
    }
}
