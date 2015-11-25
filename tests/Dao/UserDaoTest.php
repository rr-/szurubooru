<?php
namespace Szurubooru\Tests\Dao;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\User;
use Szurubooru\Services\ThumbnailService;
use Szurubooru\Tests\AbstractDatabaseTestCase;

final class UserDaoTest extends AbstractDatabaseTestCase
{
    private $fileDaoMock;
    private $thumbnailServiceMock;

    public function setUp()
    {
        parent::setUp();

        $this->fileDaoMock = $this->mock(PublicFileDao::class);
        $this->thumbnailServiceMock = $this->mock(ThumbnailService::class);
    }

    public function testRetrievingByValidName()
    {
        $userDao = $this->getUserDao();

        $user = self::getTestUser();
        $userDao->save($user);

        $expected = $user;
        $actual = $userDao->findByName($user->getName());
        $this->assertEntitiesEqual($actual, $expected);
    }

    public function testRetrievingByInvalidName()
    {
        $userDao = $this->getUserDao();

        $actual = $userDao->findByName('rubbish');

        $this->assertNull($actual);
    }

    public function testCheckingUserPresence()
    {
        $userDao = $this->getUserDao();
        $this->assertFalse($userDao->hasAnyUsers());

        $user = self::getTestUser();
        $userDao->save($user);
        $this->assertTrue($userDao->hasAnyUsers());
    }

    public function testNotLoadingAvatarContentForNewUsers()
    {
        $userDao = $this->getUserDao();
        $user = self::getTestUser();
        $user->setAvatarStyle(User::AVATAR_STYLE_MANUAL);
        $userDao->save($user);

        $this->assertNull($user->getCustomAvatarSourceContent());
    }

    public function testLoadingContentUsersForExistingUsers()
    {
        $userDao = $this->getUserDao();
        $user = self::getTestUser();
        $user->setAvatarStyle(User::AVATAR_STYLE_MANUAL);
        $userDao->save($user);

        $user = $userDao->findById($user->getId());

        $this->fileDaoMock
            ->expects($this->once())
            ->method('load')
            ->with($user->getCustomAvatarSourceContentPath())->willReturn('whatever');

        $this->assertEquals('whatever', $user->getCustomAvatarSourceContent());
    }

    public function testSavingContent()
    {
        $userDao = $this->getUserDao();
        $user = self::getTestUser();
        $user->setAvatarStyle(User::AVATAR_STYLE_MANUAL);
        $user->setCustomAvatarSourceContent('whatever');

        $this->thumbnailServiceMock
            ->expects($this->once())
            ->method('deleteUsedThumbnails')
            ->with($this->callback(
                function($subject) use ($user)
                {
                    return $subject == $user->getCustomAvatarSourceContentPath();
                }));

        $this->fileDaoMock
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(
                function($subject) use ($user)
                {
                    //callback is used because ->save() will create id, which is going to be used by the function below
                    return $subject == $user->getCustomAvatarSourceContentPath();
                }), 'whatever');

        $userDao->save($user);
    }

    private function getUserDao()
    {
        return new UserDao(
            $this->databaseConnection,
            $this->fileDaoMock,
            $this->thumbnailServiceMock);
    }
}
