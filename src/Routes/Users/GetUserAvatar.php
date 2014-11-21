<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Entities\User;
use Szurubooru\Router;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\ThumbnailService;
use Szurubooru\Services\UserService;

class GetUserAvatar extends AbstractUserRoute
{
	private $fileDao;
	private $userService;
	private $networkingService;
	private $thumbnailService;

	public function __construct(
		PublicFileDao $fileDao,
		UserService $userService,
		NetworkingService $networkingService,
		ThumbnailService $thumbnailService)
	{
		$this->fileDao = $fileDao;
		$this->userService = $userService;
		$this->networkingService = $networkingService;
		$this->thumbnailService = $thumbnailService;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/users/:userName/avatar/:size';
	}

	public function work()
	{
		$userName = $this->getArgument('userName');
		$size = $this->getArgument('size');

		try
		{
			$user = $this->userService->getByName($userName);
		}
		catch (\Exception $e)
		{
			$this->serveBlankFile($size);
		}

		switch ($user->getAvatarStyle())
		{
			case User::AVATAR_STYLE_GRAVATAR:
				$hash = md5(strtolower(trim($user->getEmail() ? $user->getEmail() : $user->getId() . $user->getName())));
				$url = 'https://www.gravatar.com/avatar/' . $hash . '?d=retro&s=' . $size;
				$this->serveFromUrl($url);
				break;

			case User::AVATAR_STYLE_BLANK:
				$this->serveBlankFile($size);
				break;

			case User::AVATAR_STYLE_MANUAL:
				$this->serveFromFile($user->getCustomAvatarSourceContentPath(), $size);
				break;

			default:
				$this->serveBlankFile($size);
				break;
		}
	}

	private function serveFromUrl($url)
	{
		$this->networkingService->redirect($url);
	}

	private function serveFromFile($sourceName, $size)
	{
		$thumbnailName = $this->thumbnailService->generateIfNeeded($sourceName, $size, $size);
		$this->networkingService->serveFile($this->fileDao->getFullPath($thumbnailName));
	}

	private function serveBlankFile($size)
	{
		$this->serveFromFile($this->getBlankAvatarSourceContentPath(), $size);
	}

	private function getBlankAvatarSourceContentPath()
	{
		return 'avatars/blank.png';
	}
}
