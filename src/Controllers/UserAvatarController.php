<?php
namespace Szurubooru\Controllers;

final class UserAvatarController extends AbstractController
{
	private $userService;
	private $fileService;
	private $httpHelper;
	private $thumbnailService;

	public function __construct(
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Helpers\HttpHelper $httpHelper,
		\Szurubooru\Services\ThumbnailService $thumbnailService)
	{
		$this->userService = $userService;
		$this->fileService = $fileService;
		$this->httpHelper = $httpHelper;
		$this->thumbnailService = $thumbnailService;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->get('/api/users/:userName/avatar/:size', [$this, 'getAvatarByName']);
	}

	public function getAvatarByName($userName, $size)
	{
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
			case \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR:
				$hash = md5(strtolower(trim($user->getEmail() ? $user->getEmail() : $user->getId() . $user->getName())));
				$url = 'https://www.gravatar.com/avatar/' . $hash . '?d=retro&s=' . $size;
				$this->serveFromUrl($url);
				break;

			case \Szurubooru\Entities\User::AVATAR_STYLE_BLANK:
				$this->serveBlankFile($size);
				break;

			case \Szurubooru\Entities\User::AVATAR_STYLE_MANUAL:
				$this->serveFromFile($user->getCustomAvatarSourceContentPath(), $size);
				break;

			default:
				$this->serveBlankFile($size);
				break;
		}
	}

	private function serveFromUrl($url)
	{
		$this->httpHelper->redirect($url);
	}

	private function serveFromFile($sourceName, $size)
	{
		$this->thumbnailService->generateIfNeeded($sourceName, $size, $size);
		$thumbnailName = $this->thumbnailService->getThumbnailName($sourceName, $size, $size);
		$this->fileService->serve($thumbnailName);
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
