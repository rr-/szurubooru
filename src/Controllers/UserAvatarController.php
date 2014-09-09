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
		$user = $this->userService->getByName($userName);

		switch ($user->avatarStyle)
		{
			case \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR:
				$hash = md5(strtolower(trim($user->email ? $user->email : $user->id . $user->name)));
				$url = 'https://www.gravatar.com/avatar/' . $hash . '?d=retro&s=' . $size;
				$this->serveFromUrl($url);
				break;

			case \Szurubooru\Entities\User::AVATAR_STYLE_BLANK:
				$this->serveFromFile($this->userService->getBlankAvatarSourcePath(), $size);
				break;

			case \Szurubooru\Entities\User::AVATAR_STYLE_MANUAL:
				$this->serveFromFile($this->userService->getCustomAvatarSourcePath($user), $size);
				break;

			default:
				$this->serveFromFile($this->userService->getBlankAvatarSourcePath(), $size);
				break;
		}
	}

	private function serveFromUrl($url)
	{
		$this->httpHelper->redirect($url);
	}

	private function serveFromFile($file, $size)
	{
		if (!$this->fileService->exists($file))
			$file = $this->userService->getBlankAvatarSourcePath();

		$sizedFile = $this->thumbnailService->getOrGenerate($file, $size, $size);
		$this->fileService->serve($sizedFile);
	}
}
