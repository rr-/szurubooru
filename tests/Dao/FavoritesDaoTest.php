<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\FavoritesDao;
use Szurubooru\Dao\PostDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Favorite;
use Szurubooru\Entities\Post;
use Szurubooru\Entities\User;
use Szurubooru\Injector;
use Szurubooru\Services\TimeService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class FavoritesDaoTest extends AbstractDatabaseTestCase
{
    public function testSaving()
    {
        $userDao = Injector::get(UserDao::class);
        $postDao = Injector::get(PostDao::class);
        $timeServiceMock = $this->mock(TimeService::class);
        $favoritesDao = new FavoritesDao(
            $this->databaseConnection, $userDao, $postDao, $timeServiceMock);

        $user = self::getTestUser('olivia');
        $userDao->save($user);

        $post = self::getTestPost();
        $postDao->save($post);

        $favorite = new Favorite();
        $favorite->setUserId($user->getId());
        $favorite->setPostId($post->getId());
        $favorite->setTime(date('c'));
        $favoritesDao->save($favorite);

        $savedFavorite = $favoritesDao->findById($favorite->getId());
        $this->assertNotNull($savedFavorite->getUserId());
        $this->assertNotNull($savedFavorite->getPostId());
        $this->assertEquals($favorite->getTime(), $savedFavorite->getTime());
        $this->assertEntitiesEqual($user, $savedFavorite->getUser());
        $this->assertEntitiesEqual($post, $savedFavorite->getPost());
    }
}
