<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $privilegeService;
	private $userService;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		\Szurubooru\Services\PrivilegeService $privilegeService,
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\UserViewProxy $userViewProxy)
	{
		$this->privilegeService = $privilegeService;
		$this->userService = $userService;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/users', [$this, 'createUser']);
		$router->get('/api/users', [$this, 'getFiltered']);
		$router->get('/api/users/:userName', [$this, 'getByName']);
		$router->put('/api/users/:userName', [$this, 'updateUser']);
		$router->delete('/api/users/:userName', [$this, 'deleteUser']);
	}

	public function getByName($userName)
	{
		$user = $this->userService->getByName($userName);
		return $this->userViewProxy->fromEntity($user);
	}

	public function getFiltered()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::LIST_USERS);

		$formData = new \Szurubooru\FormData\SearchFormData($this->inputReader);
		$searchResult = $this->userService->getFiltered($formData);
		$entities = $this->userViewProxy->fromArray($searchResult->entities);
		return [
			'data' => $entities,
			'pageSize' => $searchResult->filter->pageSize,
			'totalRecords' => $searchResult->totalRecords];
	}

	public function createUser()
	{
		$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::REGISTER);
		$formData = new \Szurubooru\FormData\RegistrationFormData($this->inputReader);
		$user = $this->userService->createUser($formData);
		return $this->userViewProxy->fromEntity($user);
	}

	public function updateUser($userName)
	{
		$formData = new \Szurubooru\FormData\UserEditFormData($this->inputReader);

		if ($formData->avatarStyle !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userName)
					? \Szurubooru\Privilege::CHANGE_OWN_AVATAR_STYLE
					: \Szurubooru\Privilege::CHANGE_ALL_AVATAR_STYLES);
		}

		if ($formData->userName !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userName)
					? \Szurubooru\Privilege::CHANGE_OWN_NAME
					: \Szurubooru\Privilege::CHANGE_ALL_NAMES);
		}

		if ($formData->password !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userName)
					? \Szurubooru\Privilege::CHANGE_OWN_PASSWORD
					: \Szurubooru\Privilege::CHANGE_ALL_PASSWORDS);
		}

		if ($formData->email !== null)
		{
			$this->privilegeService->assertPrivilege(
				$this->privilegeService->isLoggedIn($userName)
					? \Szurubooru\Privilege::CHANGE_OWN_EMAIL_ADDRESS
					: \Szurubooru\Privilege::CHANGE_ALL_EMAIL_ADDRESSES);
		}

		if ($formData->accessRank)
		{
			$this->privilegeService->assertPrivilege(\Szurubooru\Privilege::CHANGE_ACCESS_RANK);
		}

		if ($formData->browsingSettings)
		{
			$this->privilegeService->assertLoggedIn($userName);
		}

		$user = $this->userService->updateUser($userName, $formData);
		return $this->userViewProxy->fromEntity($user);
	}

	public function deleteUser($userName)
	{
		$this->privilegeService->assertPrivilege(
			$this->privilegeService->isLoggedIn($userName)
				? \Szurubooru\Privilege::DELETE_OWN_ACCOUNT
				: \Szurubooru\Privilege::DELETE_ACCOUNTS);

		return $this->userService->deleteUserByName($userName);
	}
}
