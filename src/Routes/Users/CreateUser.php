<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\FormData\RegistrationFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;

class CreateUser extends AbstractUserRoute
{
	private $privilegeService;
	private $userService;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		PrivilegeService $privilegeService,
		UserService $userService,
		InputReader $inputReader,
		UserViewProxy $userViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->userService = $userService;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function getMethods()
	{
		return ['POST'];
	}

	public function getUrl()
	{
		return '/api/users';
	}

	public function work()
	{
		$this->privilegeService->assertPrivilege(Privilege::REGISTER);
		$formData = new RegistrationFormData($this->inputReader);
		$user = $this->userService->createUser($formData);
		return $this->userViewProxy->fromEntity($user);
	}
}
