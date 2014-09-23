<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $privilegeService;
	private $userService;
	private $tokenService;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Services\TokenService $tokenService,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\UserViewProxy $userViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->userService = $userService;
		$this->tokenService = $tokenService;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
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
		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		return $this->userViewProxy->fromEntity($user);
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_USERS);

		$formData = new \Szurubooru\FormData\SearchFormData($this->inputReader);
		$searchResult = $this->userService->getFiltered($formData);
		$entities = $this->userViewProxy->fromArray($searchResult->getEntities());
		return [
			'data' => $entities,
			'pageSize' => $searchResult->getPageSize(),
			'totalRecords' => $searchResult->getTotalRecords()];
	}

	public function createUser()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::REGISTER);
		$formData = new \Szurubooru\FormData\RegistrationFormData($this->inputReader);
		$user = $this->userService->createUser($formData);
		return $this->userViewProxy->fromEntity($user);
	}

	public function updateUser($userNameOrEmail)
	{
		$user = $this->userService->getByNameOrEmail($userNameOrEmail);
		$formData = new \Szurubooru\FormData\UserEditFormData($this->inputReader);

		if ($formData->avatarStyle !== null || $formData->avatarContent !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? \Szurubooru\Privilege::CHANGE_OWN_AVATAR_STYLE
					: \Szurubooru\Privilege::CHANGE_ALL_AVATAR_STYLES);
		}

		if ($formData->userName !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? \Szurubooru\Privilege::CHANGE_OWN_NAME
					: \Szurubooru\Privilege::CHANGE_ALL_NAMES);
		}

		if ($formData->password !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? \Szurubooru\Privilege::CHANGE_OWN_PASSWORD
					: \Szurubooru\Privilege::CHANGE_ALL_PASSWORDS);
		}

		if ($formData->email !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userNameOrEmail)
					? \Szurubooru\Privilege::CHANGE_OWN_EMAIL_ADDRESS
					: \Szurubooru\Privilege::CHANGE_ALL_EMAIL_ADDRESSES);
		}

		if ($formData->accessRank)
		{
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_ACCESS_RANK);
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
				? \Szurubooru\Privilege::DELETE_OWN_ACCOUNT
				: \Szurubooru\Privilege::DELETE_ACCOUNTS);

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
