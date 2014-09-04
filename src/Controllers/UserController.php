<?php
namespace Szurubooru\Controllers;

final class UserController extends AbstractController
{
	private $inputReader;
	private $userService;

	public function __construct(
		\Szurubooru\Services\UserService $userService,
		\Szurubooru\Helpers\InputReader $inputReader)
	{
		$this->inputReader = $inputReader;
		$this->userService = $userService;
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
		//todo: privilege checking
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
		//todo: privilege checking
		$user = $this->userService->getByName($name);
		if (!$user)
			throw new \DomainException('User with name "' . $name . '" was not found.');
		return new \Szurubooru\ViewProxies\User($user);
	}

	public function register()
	{
		//todo: privilege checking
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
