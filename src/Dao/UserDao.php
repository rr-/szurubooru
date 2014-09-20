<?php
namespace Szurubooru\Dao;

class UserDao extends AbstractDao implements ICrudDao
{
	private $fileService;
	private $thumbnailService;

	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\ThumbnailService $thumbnailService)
	{
		parent::__construct(
			$databaseConnection,
			'users',
			new \Szurubooru\Dao\EntityConverters\UserEntityConverter());

		$this->fileService = $fileService;
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

	protected function afterLoad(\Szurubooru\Entities\Entity $user)
	{
		$user->setLazyLoader(
			\Szurubooru\Entities\User::LAZY_LOADER_CUSTOM_AVATAR_SOURCE_CONTENT,
			function(\Szurubooru\Entities\User $user)
			{
				$avatarSource = $user->getCustomAvatarSourceContentPath();
				return $this->fileService->load($avatarSource);
			});
	}

	protected function afterSave(\Szurubooru\Entities\Entity $user)
	{
		$targetPath = $user->getCustomAvatarSourceContentPath();
		$content = $user->getCustomAvatarSourceContent();
		if ($content)
			$this->fileService->save($targetPath, $content);
		else
			$this->fileService->delete($targetPath);
		$this->thumbnailService->deleteUsedThumbnails($targetPath);
	}

	protected function afterDelete(\Szurubooru\Entities\Entity $user)
	{
		$avatarSource = $user->getCustomAvatarSourceContentPath();
		$this->fileService->delete($avatarSource);
		$this->thumbnailService->deleteUsedThumbnails($avatarSource);
	}
}
