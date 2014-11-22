<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Config;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\SearchServices\Parsers\UserSearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;
use Szurubooru\ViewProxies\UserViewProxy;

class GetUsers extends AbstractUserRoute
{
	private $config;
	private $privilegeService;
	private $userService;
	private $userSearchParser;
	private $inputReader;
	private $userViewProxy;

	public function __construct(
		Config $config,
		PrivilegeService $privilegeService,
		UserService $userService,
		UserSearchParser $userSearchParser,
		InputReader $inputReader,
		UserViewProxy $userViewProxy)
	{
		$this->config = $config;
		$this->privilegeService = $privilegeService;
		$this->userService = $userService;
		$this->userSearchParser = $userSearchParser;
		$this->inputReader = $inputReader;
		$this->userViewProxy = $userViewProxy;
	}

	public function getMethods()
	{
		return ['GET'];
	}

	public function getUrl()
	{
		return '/api/users';
	}

	public function work($args)
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
}
