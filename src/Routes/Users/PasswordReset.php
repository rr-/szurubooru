<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\UserService;

class PasswordReset extends AbstractUserRoute
{
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getMethods()
    {
        return ['POST', 'PUT'];
    }

    public function getUrl()
    {
        return '/api/password-reset/:userNameOrEmail';
    }

    public function work($args)
    {
        $user = $this->userService->getByNameOrEmail($args['userNameOrEmail']);
        return $this->userService->sendPasswordResetEmail($user);
    }
}
