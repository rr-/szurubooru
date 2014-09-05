<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $authService;
	private $userService;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Helpers\InputReader $inputReader,
		\Szurubooru\Controllers\ViewProxies\UserViewProxy $userViewProxy)
	{
		$this->authService = $authService;
		$this->userService = $userService;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/users', [$this, 'register']);
		$router->get('/api/users', [$this, 'getFiltered']);
		$router->get('/api/users/:name', [$this, 'getByName']);
		$router->put('/api/users/:name', [$this, 'update']);
		$router->delete('/api/users/:name', [$this, 'delete']);
	}

	public function getByName($name)
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_VIEW_USER);

		$user = $this->userService->getByName($name);
		if (!$user)
			throw new \DomainException('User with name "' . $name . '" was not found.');
		return $this->userViewProxy->fromEntity($user);
	}

	public function getFiltered()
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_LIST_USERS);

		$searchFormData = new \Szurubooru\FormData\SearchFormData($this->inputReader);
		$searchResult = $this->userService->getFiltered($searchFormData);
		$entities = $this->userViewProxy->fromArray($searchResult->entities);
		return [
			'data' => $entities,
			'pageSize' => $searchResult->filter->pageSize,
			'totalRecords' => $searchResult->totalRecords];
	}

	public function register()
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_REGISTER);

		$input = new \Szurubooru\FormData\RegistrationFormData($this->inputReader);
		$user = $this->userService->register($input);
		return $this->userViewProxy->fromEntity($user);
	}

	public function update($name)
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function delete($name)
	{
		if ($name == $this->authService->getLoggedInUser()->name)
			$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_DELETE_OWN_ACCOUNT);
		else
			$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_DELETE_ACCOUNTS);
		return $this->userService->deleteByName($name);
	}
}
