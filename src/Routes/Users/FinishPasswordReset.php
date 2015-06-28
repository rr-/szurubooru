<?php
namespace Szurubooru\Routes\Users;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;

class FinishPasswordReset extends AbstractUserRoute
{
    private $userService;
    private $tokenService;

    public function __construct(
        UserService $userService,
        TokenService $tokenService)
    {
        $this->userService = $userService;
        $this->tokenService = $tokenService;
    }

    public function getMethods()
    {
        return ['POST', 'PUT'];
    }

    public function getUrl()
    {
        return '/api/finish-password-reset/:tokenName';
    }

    public function work($args)
    {
        $token = $this->tokenService->getByName($args['tokenName']);
        return ['newPassword' => $this->userService->finishPasswordReset($token)];
    }
}
