<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\UserService;

class DeleteUser extends AbstractUserRoute
{
    private $privilegeService;
    private $userService;

    public function __construct(
        PrivilegeService $privilegeService,
        UserService $userService)
    {
        $this->privilegeService = $privilegeService;
        $this->userService = $userService;
    }

    public function getMethods()
    {
        return ['DELETE'];
    }

    public function getUrl()
    {
        return '/api/users/:userNameOrEmail';
    }

    public function work($args)
    {
        $userNameOrEmail = $args['userNameOrEmail'];

        $this->privilegeService->assertPrivilege(
            $this->privilegeService->isLoggedIn($userNameOrEmail)
                ? Privilege::DELETE_OWN_ACCOUNT
                : Privilege::DELETE_ALL_ACCOUNTS);

        $user = $this->userService->getByNameOrEmail($userNameOrEmail);
        return $this->userService->deleteUser($user);
    }
}
