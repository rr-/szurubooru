<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Privilege;
use Szurubooru\Search\Parsers\UserSearchParser;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;
use Szurubooru\ViewProxies\UserViewProxy;

class GetUser extends AbstractUserRoute
{
    private $privilegeService;
    private $userService;
    private $userSearchParser;
    private $userViewProxy;

    public function __construct(
        PrivilegeService $privilegeService,
        UserService $userService,
        UserSearchParser $userSearchParser,
        UserViewProxy $userViewProxy)
    {
        $this->privilegeService = $privilegeService;
        $this->userService = $userService;
        $this->userSearchParser = $userSearchParser;
        $this->userViewProxy = $userViewProxy;
    }

    public function getMethods()
    {
        return ['GET'];
    }

    public function getUrl()
    {
        return '/api/users/:userNameOrEmail';
    }

    public function work($args)
    {
        $userNameOrEmail = $args['userNameOrEmail'];
        if (!$this->privilegeService->isLoggedIn($userNameOrEmail))
            $this->privilegeService->assertPrivilege(Privilege::VIEW_USERS);
        $user = $this->userService->getByNameOrEmail($userNameOrEmail);
        return $this->userViewProxy->fromEntity($user);
    }
}
