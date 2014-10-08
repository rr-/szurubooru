<?php
namespace Szurubooru\Controllers;
use Szurubooru\Dao\PublicFileDao;
use Szurubooru\Entities\User;
use Szurubooru\Helpers\HttpHelper;
use Szurubooru\Router;
use Szurubooru\Services\NetworkingService;
use Szurubooru\Services\ThumbnailService;
use Szurubooru\Services\UserService;

final class UserAvatarController extends AbstractController
{
	private $fileDao;
	private $userService;
	private $networkingService;
	private $httpHelper;
	private $thumbnailService;

	public function __construct(
		PublicFileDao $fileDao,
		UserService $userService,
		NetworkingService $networkingService,
		HttpHelper $httpHelper,
		ThumbnailService $thumbnailService)
	{
		$this->fileDao = $fileDao;
		$this->userService = $userService;
		$this->networkingService = $networkingService;
		$this->httpHelper = $httpHelper;
		$this->thumbnailService = $thumbnailService;
	}

	public function registerRoutes(Router $router)
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
		$this->httpHelper->redirect($url);
	}

	private function serveFromFile($sourceName, $size)
	{
		$this->thumbnailService->generateIfNeeded($sourceName, $size, $size);
		$thumbnailName = $this->thumbnailService->getThumbnailName($sourceName, $size, $size);
		$this->networkingService->serve($this->fileDao->getFullPath($thumbnailName));
	}

	private function serveBlankFile($size)
	{
		$this->serveFromFile($this->fileDao->getFullPath($this->getBlankAvatarSourceContentPath()), $size);
	}

	private function getBlankAvatarSourceContentPath()
	{
		return 'avatars/blank.png';
	}
}
