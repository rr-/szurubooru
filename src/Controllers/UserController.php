<?php
namespace Szurubooru\Controllers;
use Szurubooru\Config;
use Szurubooru\Controllers\ViewProxies\UserViewProxy;
use Szurubooru\FormData\RegistrationFormData;
use Szurubooru\FormData\UserEditFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Router;
use Szurubooru\SearchServices\Parsers\UserSearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;

final class UserController extends AbstractController
{
	private $config;
	private $privilegeService;
	private $userService;
	private $tokenService;
	private $userSearchParser;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		Config $config,
		PrivilegeService $privilegeService,
		UserService $userService,
		TokenService $tokenService,
		UserSearchParser $userSearchParser,
		InputReader $inputReader,
		UserViewProxy $userViewProxy)
	{
		$this->config = $config;
		$this->privilegeService = $privilegeService;
		$this->userService = $userService;
		$this->tokenService = $tokenService;
		$this->userSearchParser = $userSearchParser;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function registerRoutes(Router $router)
	{
		$router->post('/api/users', [$this, 'createUser']);
		$router->get('/api/users', [$this, 'getFiltered']);
		$router->get('/api/users/:userNameOrEmail', [$this, 'getByNameOrEmail']);
		$router->put('/api/users/:userNameOrEmail', [$this, 'updateUser']);
		$router->delete('/api/users/:userNameOrEmail', [$this, 'deleteUser']);
		$router->post('/api/password-reset/:userNameOrEmail', [$this, 'passwordReset']);
		$router->post('/api/finish-password-reset/:tokenName', [$this, 'finishPasswordReset']);
		$router->post('/api/activation/:userNameOrEmail', [$this, 'activation']);
		$router->post('/api/finish-activation/:tokenName', [$this, 'finishActivation']);
	}

	public function getByNameOrEmail($userNameOrEmail)
	{
		$this->privilegeService->assertPrivilege(Privilege::VIEW_USERS);
		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		return $this->userViewProxy->fromEntity($user);
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(Privilege::LIST_USERS);

		$filter = $this->userSearchParser->createFilterFromInputReader($this->inputReader);
		$filter->setPageSize($this->config->users->usersPerPage);
		$result = $this->userService->getFiltered($filter);
		$entities = $this->userViewProxy->fromArray($result->getEntities());
		return [
			'data' => $entities,
			'pageSize' => $result->getPageSize(),
			'totalRecords' => $result->getTotalRecords()];
	}

	public function createUser()
	{
		$this->privilegeService->assertPrivilege(Privilege::REGISTER);
		$formData = new RegistrationFormData($this->inputReader);
		$user = $this->userService->createUser($formData);
		return $this->userViewProxy->fromEntity($user);
	}

	public function updateUser($userNameOrEmail)
	{
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

		$user = $this->userService->updateUser($user, $formData);
		return $this->userViewProxy->fromEntity($user);
	}

	public function deleteUser($userNameOrEmail)
	{
		$this->privilegeService->assertPrivilege(
			$this->privilegeService->isLoggedIn($userNameOrEmail)
				? Privilege::DELETE_OWN_ACCOUNT
				: Privilege::DELETE_ACCOUNTS);

		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		return $this->userService->deleteUser($user);
	}

	public function passwordReset($userNameOrEmail)
	{
		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		return $this->userService->sendPasswordResetEmail($user);
	}

	public function activation($userNameOrEmail)
	{
		$user = $this->userService->getByNameOrEmail($userNameOrEmail, true);
		return $this->userService->sendActivationEmail($user);
	}

	public function finishPasswordReset($tokenName)
	{
		$token = $this->tokenService->getByName($tokenName);
		return ['newPassword' => $this->userService->finishPasswordReset($token)];
	}

	public function finishActivation($tokenName)
	{
		$token = $this->tokenService->getByName($tokenName);
		$this->userService->finishActivation($token);
	}
}
