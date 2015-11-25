<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\TagDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Tag;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Services\ThumbnailService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class PostDaoTest extends AbstractDatabaseTestCase
{
    private $fileDaoMock;
    private $thumbnailServiceMock;
    private $tagDao;
    private $userDao;

    public function setUp()
    {
        parent::setUp();

        $this->tagDao = new TagDao($this->databaseConnection);

        $fileDaoMock = $this->mock(PublicFileDao::class);
    }

    public function testCreating()
    {
        $postDao = Injector::get(PostDao::class);

        $post = self::getTestPost();
        $savedPost = $postDao->save($post);
        $this->assertEquals('test', $post->getName());
        $this->assertNotNull($savedPost->getId());
    }

    public function testUpdating()
    {
        $postDao = Injector::get(PostDao::class);

        $post = self::getTestPost();
        $post = $postDao->save($post);
        $this->assertEquals('test', $post->getName());
        $id = $post->getId();
        $post->setName($post->getName() . '2');
        $post = $postDao->save($post);
        $this->assertEquals('test2', $post->getName());
        $this->assertEquals($id, $post->getId());
    }

    public function testGettingAll()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);
        $this->assertNotNull($post1->getId());
        $this->assertNotNull($post2->getId());
        $this->assertNotEquals($post1->getId(), $post2->getId());

        $actual = $postDao->findAll();

        $expected = [
            $post1->getId() => $post1,
            $post2->getId() => $post2,
        ];

        $this->assertEntitiesEqual($expected, $actual);
        $this->assertEquals(count($expected), $postDao->getCount());
    }

    public function testGettingTotalFileSize()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $post3 = self::getTestPost();
        $post1->setOriginalFileSize(1249812);
        $post2->setOriginalFileSize(128);
        $post3->setOriginalFileSize(null);
        $postDao->save($post1);
        $postDao->save($post2);
        $postDao->save($post3);
        $expectedFileSize =
            $post1->getOriginalFileSize() +
            $post2->getOriginalFileSize() +
            $post3->getOriginalFileSize();
        $this->assertGreaterThan(0, $expectedFileSize);
        $this->assertEquals($expectedFileSize, $postDao->getTotalFileSize());
    }

    public function testGettingById()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);

        $actualPost1 = $postDao->findById($post1->getId());
        $actualPost2 = $postDao->findById($post2->getId());
        $this->assertEntitiesEqual($post1, $actualPost1);
        $this->assertEntitiesEqual($post2, $actualPost2);
    }

    public function testDeletingAll()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);

        $postDao->deleteAll();

        $actualPost1 = $postDao->findById($post1->getId());
        $actualPost2 = $postDao->findById($post2->getId());
        $this->assertNull($actualPost1);
        $this->assertNull($actualPost2);
        $this->assertEquals(0, count($postDao->findAll()));
    }

    public function testDeletingById()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $postDao->save($post1);
        $postDao->save($post2);

        $postDao->deleteById($post1->getId());

        $actualPost1 = $postDao->findById($post1->getId());
        $actualPost2 = $postDao->findById($post2->getId());
        $this->assertNull($actualPost1);
        $this->assertEntitiesEqual($actualPost2, $actualPost2);
        $this->assertEquals(1, count($postDao->findAll()));
    }

    public function testFindingByTagName()
    {
        $tagDao = Injector::get(TagDao::class);
        $postDao = Injector::get(PostDao::class);

        $tag1 = new Tag();
        $tag1->setName('tag1');
        $tag1->setCreationTime(date('c'));
        $tag2 = new Tag();
        $tag2->setName('tag2');
        $tag2->setCreationTime(date('c'));
        $tagDao->save($tag1);
        $tagDao->save($tag2);

        $post1 = self::getTestPost();
        $post1->setTags([$tag1]);
        $postDao->save($post1);
        $post2 = self::getTestPost();
        $post2->setTags([$tag2]);
        $postDao->save($post2);

        $this->assertEntitiesEqual([$post1], array_values($postDao->findByTagName('tag1')));
        $this->assertEntitiesEqual([$post2], array_values($postDao->findByTagName('tag2')));
    }

    public function testSavingTags()
    {
        $tagDao = Injector::get(TagDao::class);
        $postDao = Injector::get(PostDao::class);

        $tag1 = new Tag();
        $tag1->setName('tag1');
        $tag1->setCreationTime(date('c'));
        $tag2 = new Tag();
        $tag2->setName('tag2');
        $tag2->setCreationTime(date('c'));
        $this->tagDao->save($tag1);
        $this->tagDao->save($tag2);
        $testTags = ['tag1' => $tag1, 'tag2' => $tag2];

        $post = self::getTestPost();
        $post->setTags($testTags);
        $postDao->save($post);

        $savedPost = $postDao->findById($post->getId());
        $this->assertEntitiesEqual($testTags, $post->getTags());
        $this->assertEquals(2, count($savedPost->getTags()));

        $this->assertEquals(2, $post->getTagCount());
        $this->assertEquals(2, $savedPost->getTagCount());

        $this->assertEquals(2, count($tagDao->findAll()));
    }

    public function testSavingUnsavedRelations()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $testPosts = [$post1, $post2];

        $post = self::getTestPost();
        $post->setRelatedPosts($testPosts);
        $this->setExpectedException(\Exception::class, 'Unsaved entities found');
        $postDao->save($post);
    }

    public function testSavingRelations()
    {
        $postDao = Injector::get(PostDao::class);

        $post1 = self::getTestPost();
        $post2 = self::getTestPost();
        $testPosts = [$post1, $post2];

        $postDao->save($post1);
        $postDao->save($post2);
        $post = self::getTestPost();
        $post->setRelatedPosts($testPosts);
        $postDao->save($post);

        $savedPost = $postDao->findById($post->getId());
        $this->assertEntitiesEqual($testPosts, $post->getRelatedPosts());
        $this->assertEquals(2, count($savedPost->getRelatedPosts()));
    }

    public function testSavingUser()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);

        $user = self::getTestUser('uploader');
        $userDao->save($user);

        $post = self::getTestPost();
        $post->setUser($user);
        $postDao->save($post);

        $savedPost = $postDao->findById($post->getId());
        $this->assertNotNull($post->getUserId());
        $this->assertEntitiesEqual($user, $post->getUser());
    }

    public function testNotLoadingContentForNewPosts()
    {
        $postDao = Injector::get(PostDao::class);
        $newlyCreatedPost = self::getTestPost();
        $this->assertNull($newlyCreatedPost->getContent());
    }

    public function testLoadingContentPostsForExistingPosts()
    {
        $thumbnailServiceMock = $this->mock(ThumbnailService::class);
        $fileDaoMock = $this->mock(PublicFileDao::class);

        $tagDao = Injector::get(TagDao::class);
        $userDao = Injector::get(UserDao::class);
        $postDao = new PostDao(
            $this->databaseConnection,
            $tagDao, $userDao, $fileDaoMock, $thumbnailServiceMock);

        $post = self::getTestPost();
        $postDao->save($post);

        $post = $postDao->findById($post->getId());

        $fileDaoMock
            ->expects($this->once())
            ->method('load')
            ->with($post->getContentPath())
            ->willReturn('whatever');

        $this->assertEquals('whatever', $post->getContent());
    }

    public function testSavingContent()
    {
        $thumbnailServiceMock = $this->mock(ThumbnailService::class);
        $fileDaoMock = $this->mock(PublicFileDao::class);

        $tagDao = Injector::get(TagDao::class);
        $userDao = Injector::get(UserDao::class);
        $postDao = new PostDao(
            $this->databaseConnection,
            $tagDao, $userDao, $fileDaoMock, $thumbnailServiceMock);

        $post = self::getTestPost();
        $post->setContent('whatever');

        $thumbnailServiceMock
            ->expects($this->exactly(2))
            ->method('deleteUsedThumbnails')
            ->withConsecutive(
                [$post->getContentPath()],
                [$post->getThumbnailSourceContentPath()]);

        $fileDaoMock
            ->expects($this->exactly(1))
            ->method('save')
            ->withConsecutive(
                [$post->getContentPath(), 'whatever']);

        $postDao->save($post);
    }

    public function testSavingContentAndThumbnail()
    {
        $thumbnailServiceMock = $this->mock(ThumbnailService::class);
        $fileDaoMock = $this->mock(PublicFileDao::class);

        $tagDao = Injector::get(TagDao::class);
        $userDao = Injector::get(UserDao::class);
        $postDao = new PostDao(
            $this->databaseConnection,
            $tagDao, $userDao, $fileDaoMock, $thumbnailServiceMock);

        $post = self::getTestPost();
        $post->setContent('whatever');
        $post->setThumbnailSourceContent('an image of sharks');

        $thumbnailServiceMock
            ->expects($this->exactly(2))
            ->method('deleteUsedThumbnails')
            ->withConsecutive(
                [$post->getContentPath()],
                [$post->getThumbnailSourceContentPath()]);

        $fileDaoMock
            ->expects($this->exactly(2))
            ->method('save')
            ->withConsecutive(
                [$post->getContentPath(), 'whatever'],
                [$post->getThumbnailSourceContentPath(), 'an image of sharks']);

        $postDao->save($post);
    }
}
