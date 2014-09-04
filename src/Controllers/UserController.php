<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $authService;
	private $userService;
	private $inputReader;

	public function __construct(
		\Szurubooru\Services\AuthService $authService,
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->authService = $authService;
		$this->userService = $userService;
		$this->inputReader = $inputReader;
	}

	public function registerRoutes(\Szurubooru\Router $router)
	{
		$router->post('/api/users', [$this, 'register']);
		$router->get('/api/users', [$this, 'getFiltered']);
		$router->get('/api/users/:name', [$this, 'getByName']);
		$router->put('/api/users/:name', [$this, 'update']);
		$router->delete('/api/users/:name', [$this, 'delete']);
	}

	public function getFiltered()
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_LIST_USERS);

		//todo: move this to form data constructor
		$searchFormData = new \Szurubooru\FormData\SearchFormData;
		$searchFormData->query = $this->inputReader->query;
		$searchFormData->order = $this->inputReader->order;
		$searchFormData->pageNumber = $this->inputReader->page;
		$searchResult = $this->userService->getFiltered($searchFormData);
		$entities = array_map(function($user) { return new \Szurubooru\ViewProxies\User($user); }, $searchResult->entities);
		return [
			'data' => $entities,
			'pageSize' => $searchResult->filter->pageSize,
			'totalRecords' => $searchResult->totalRecords];
	}

	public function getByName($name)
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_VIEW_USER);

		$user = $this->userService->getByName($name);
		if (!$user)
			throw new \DomainException('User with name "' . $name . '" was not found.');
		return new \Szurubooru\ViewProxies\User($user);
	}

	public function register()
	{
		$this->authService->assertPrivilege(\Szurubooru\Privilege::PRIVILEGE_REGISTER);

		$input = new \Szurubooru\FormData\RegistrationFormData;
		//todo: move this to form data constructor
		$input->name = $this->inputReader->userName;
		$input->password = $this->inputReader->password;
		$input->email = $this->inputReader->email;
		$user = $this->userService->register($input);
		return new \Szurubooru\ViewProxies\User($user);
	}

	public function update($name)
	{
		throw new \BadMethodCallException('Not implemented');
	}

	public function delete($name)
	{
		throw new \BadMethodCallException('Not implemented');
	}
}
