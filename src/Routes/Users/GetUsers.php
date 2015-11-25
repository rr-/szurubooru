<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Config;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Search\ParserConfigs\UserSearchParserConfig;
use Szurubooru\Search\SearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;
use Szurubooru\ViewProxies\UserViewProxy;

class GetUsers extends AbstractUserRoute
{
    private $config;
    private $privilegeService;
    private $userService;
    private $searchParser;
    private $inputReader;
    private $userViewProxy;

    public function __construct(
        Config $config,
        PrivilegeService $privilegeService,
        UserService $userService,
        UserSearchParserConfig $searchParserConfig,
        InputReader $inputReader,
        UserViewProxy $userViewProxy)
    {
        $this->config = $config;
        $this->privilegeService = $privilegeService;
        $this->userService = $userService;
        $this->searchParser = new SearchParser($searchParserConfig);
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

        $filter = $this->searchParser->createFilterFromInputReader($this->inputReader);
        $filter->setPageSize($this->config->users->usersPerPage);
        $result = $this->userService->getFiltered($filter);
        $entities = $this->userViewProxy->fromArray($result->getEntities());
        return [
            'users' => $entities,
            'pageSize' => $result->getPageSize(),
            'totalRecords' => $result->getTotalRecords()];
    }
}
