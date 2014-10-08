<?php
namespace Szurubooru\Dao;
use Szurubooru\Dao\EntityConverters\UserEntityConverter;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\DatabaseConnection;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\User;
use Szurubooru\Services\ThumbnailService;

class UserDao extends AbstractDao implements ICrudDao
{
	const ORDER_NAME = 'name';
	const ORDER_REGISTRATION_TIME = 'registrationTime';

	private $fileDao;
	private $thumbnailService;

	public function __construct(
		DatabaseConnection $databaseConnection,
		PublicFileDao $fileDao,
		ThumbnailService $thumbnailService)
	{
		parent::__construct(
			$databaseConnection,
			'users',
			new UserEntityConverter());

		$this->fileDao = $fileDao;
		$this->thumbnailService = $thumbnailService;
	}

	public function findByName($userName)
	{
		return $this->findOneBy('name', $userName);
	}

	public function findByEmail($userEmail, $allowUnconfirmed = false)
	{
		$result = $this->findOneBy('email', $userEmail);
		if (!$result and $allowUnconfirmed)
		{
			$result = $this->findOneBy('emailUnconfirmed', $userEmail);
		}
		return $result;
	}

	public function hasAnyUsers()
	{
		return $this->hasAnyRecords();
	}

	public function deleteByName($userName)
	{
		$this->deleteBy('name', $userName);
		$this->fpdo->deleteFrom('tokens')->where('additionalData', $userName);
	}

	protected function afterLoad(Entity $user)
	{
		$user->setLazyLoader(
			User::LAZY_LOADER_CUSTOM_AVATAR_SOURCE_CONTENT,
			function(User $user)
			{
				$avatarSource = $user->getCustomAvatarSourceContentPath();
				return $this->fileDao->load($avatarSource);
			});
	}

	protected function afterSave(Entity $user)
	{
		$targetPath = $user->getCustomAvatarSourceContentPath();
		$content = $user->getCustomAvatarSourceContent();
		if ($content)
			$this->fileDao->save($targetPath, $content);
		else
			$this->fileDao->delete($targetPath);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	protected function afterDelete(Entity $user)
	{
		$avatarSource = $user->getCustomAvatarSourceContentPath();
		$this->fileDao->delete($avatarSource);
		$this->thumbnailService->deleteUsedThumbnails($avatarSource);
	}
}
