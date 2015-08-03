<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\UserService;

class ActivateAccount extends AbstractUserRoute
{
    private $userService;

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
        return '/api/activation/:userNameOrEmail';
    }

    public function work($args)
    {
        $user = $this->userService->getByNameOrEmail($args['userNameOrEmail'], true);
        $this->userService->sendActivationEmail($user);
    }
}
