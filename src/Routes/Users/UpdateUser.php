<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\FormData\UserEditFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;

class UpdateUser extends AbstractUserRoute
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
		return '/api/users/:userNameOrEmail';
	}

	public function work($args)
	{
		$userNameOrEmail = $args['userNameOrEmail'];

		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		$formData = new UserEditFormData($this->inputReader);

		if ($formData->avatarStyle !== null || $formData->avatarContent !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? Privilege::CHANGE_OWN_AVATAR_STYLE
					: Privilege::CHANGE_ALL_AVATAR_STYLES);
		}

		if ($formData->userName !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? Privilege::CHANGE_OWN_NAME
					: Privilege::CHANGE_ALL_NAMES);
		}

		if ($formData->password !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? Privilege::CHANGE_OWN_PASSWORD
					: Privilege::CHANGE_ALL_PASSWORDS);
		}

		if ($formData->email !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? Privilege::CHANGE_OWN_EMAIL_ADDRESS
					: Privilege::CHANGE_ALL_EMAIL_ADDRESSES);
		}

		if ($formData->accessRank)
		{
			$this->privilegeService->assertPrivilege(Privilege::CHANGE_ACCESS_RANK);
		}

		if ($formData->browsingSettings)
		{
			$this->privilegeService->assertLoggedIn($userNameOrEmail);
		}

		if ($formData->banned !== null)
		{
			$this->privilegeService->assertPrivilege(Privilege::BAN_USERS);
		}

		$user = $this->userService->updateUser($user, $formData);
		return $this->userViewProxy->fromEntity($user);
	}
}
