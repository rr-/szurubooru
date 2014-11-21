<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;

class FinishActivation extends AbstractUserRoute
{
	private $userService;
	private $tokenService;

	public function __construct(
		UserService $userService,
		TokenService $tokenService)
	{
		$this->userService = $userService;
		$this->tokenService = $tokenService;
	}

	public function getMethods()
	{
		return ['POST', 'PUT'];
	}

	public function getUrl()
	{
		return '/api/finish-activation/:tokenName';
	}

	public function work()
	{
		$token = $this->tokenService->getByName($this->getArgument('tokenName'));
		$this->userService->finishActivation($token);
	}
}
