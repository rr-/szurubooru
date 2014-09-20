<?php
namespace Szurubooru\Tests\Dao;

final class UserDaoTest extends \Szurubooru\Tests\AbstractDatabaseTestCase
{
	private $fileServiceMock;
	private $thumbnailServiceMock;

	public function setUp()
	{
		parent::setUp();

		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
	}

	public function testRetrievingByValidName()
	{
		$userDao = $this->getUserDao();

		$user = $this->getTestUser();
		$userDao->save($user);

		$expected = $user;
		$actual = $userDao->findByName($user->getName());
		$actual->resetLazyLoaders();
		$this->assertEquals($actual, $expected);
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

		$user = $this->getTestUser();
		$userDao->save($user);
		$this->assertTrue($userDao->hasAnyUsers());
	}

	public function testNotLoadingAvatarContentForNewUsers()
	{
		$userDao = $this->getUserDao();
		$user = $this->getTestUser();
		$user->setAvatarStyle(\Szurubooru\Entities\User::AVATAR_STYLE_MANUAL);
		$userDao->save($user);

		$this->assertNull($user->getCustomAvatarSourceContent());
	}

	public function testLoadingContentUsersForExistingUsers()
	{
		$userDao = $this->getUserDao();
		$user = $this->getTestUser();
		$user->setAvatarStyle(\Szurubooru\Entities\User::AVATAR_STYLE_MANUAL);
		$userDao->save($user);

		$user = $userDao->findById($user->getId());

		$this->fileServiceMock
			->expects($this->once())
			->method('load')
			->with($user->getCustomAvatarSourceContentPath())->willReturn('whatever');

		$this->assertEquals('whatever', $user->getCustomAvatarSourceContent());
	}

	public function testSavingContent()
	{
		$userDao = $this->getUserDao();
		$user = $this->getTestUser();
		$user->setAvatarStyle(\Szurubooru\Entities\User::AVATAR_STYLE_MANUAL);
		$user->setCustomAvatarSourceContent('whatever');

		$this->thumbnailServiceMock
			->expects($this->once())
			->method('deleteUsedThumbnails')
			->with($this->callback(
				function($subject) use ($user)
				{
					return $subject == $user->getCustomAvatarSourceContentPath();
				}));

		$this->fileServiceMock
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
		return new \Szurubooru\Dao\UserDao(
			$this->databaseConnection,
			$this->fileServiceMock,
			$this->thumbnailServiceMock);
	}

	private function getTestUser()
	{
		$user = new \Szurubooru\Entities\User();
		$user->setName('test');
		$user->setPasswordHash('whatever');
		$user->setLastLoginTime('whatever');
		$user->setRegistrationTime('whatever');
		$user->setAccessRank(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER);
		return $user;
	}
}
