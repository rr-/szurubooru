<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Entities\Tag;
use Szurubooru\Injector;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class TagDaoTest extends AbstractDatabaseTestCase
{
    public function testSaving()
    {
        $tagDao = Injector::get(TagDao::class);

        $tag = self::getTestTag('test');
        $tag->setCreationTime(date('c', mktime(0, 0, 0, 10, 1, 2014)));
        $this->assertFalse($tag->isBanned());
        $tag->setBanned(true);

        $tagDao->save($tag);
        $actualTag = $tagDao->findById($tag->getId());
        $this->assertEntitiesEqual($tag, $actualTag);
    }

    public function testSavingRelations()
    {
        $tagDao = Injector::get(TagDao::class);

        $tag1 = self::getTestTag('test 1');
        $tag2 = self::getTestTag('test 2');
        $tag3 = self::getTestTag('test 3');
        $tag4 = self::getTestTag('test 4');
        $tagDao->save($tag1);
        $tagDao->save($tag2);
        $tagDao->save($tag3);
        $tagDao->save($tag4);

        $tag = self::getTestTag('test');
        $tag->setImpliedTags([$tag1, $tag3]);
        $tag->setSuggestedTags([$tag2, $tag4]);

        $this->assertGreaterThan(0, count($tag->getImpliedTags()));
        $this->assertGreaterThan(0, count($tag->getSuggestedTags()));

        $tagDao->save($tag);
        $actualTag = $tagDao->findById($tag->getId());

        $this->assertEntitiesEqual($tag, $actualTag);
        $this->assertEntitiesEqual(array_values($tag->getImpliedTags()), array_values($actualTag->getImpliedTags()));
        $this->assertEntitiesEqual(array_values($tag->getSuggestedTags()), array_values($actualTag->getSuggestedTags()));
        $this->assertGreaterThan(0, count($actualTag->getImpliedTags()));
        $this->assertGreaterThan(0, count($actualTag->getSuggestedTags()));
    }

    public function testFindByPostIds()
    {
        $postDao = Injector::get(PostDao::class);
        $tagDao = Injector::get(TagDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);

        $tag1 = self::getTestTag('test1');
        $tag2 = self::getTestTag('test2');
        $tagDao->save($tag1);
        $tagDao->save($tag2);

        $pdo = $this->databaseConnection->getPDO();
        $pdo->exec(sprintf('INSERT INTO postTags(postId, tagId) VALUES (%d, %d)', $post1->getId(), $tag1->getId()));
        $pdo->exec(sprintf('INSERT INTO postTags(postId, tagId) VALUES (%d, %d)', $post2->getId(), $tag1->getId()));
        $pdo->exec(sprintf('INSERT INTO postTags(postId, tagId) VALUES (%d, %d)', $post1->getId(), $tag2->getId()));
        $pdo->exec(sprintf('INSERT INTO postTags(postId, tagId) VALUES (%d, %d)', $post2->getId(), $tag2->getId()));

        $expected = [
            $tag1->getId() => $tag1,
            $tag2->getId() => $tag2,
        ];
        $actual = $tagDao->findByPostId($post1->getId());
        $this->assertEntitiesEqual($expected, $actual);
    }
}
