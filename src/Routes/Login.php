<?php
namespace Szurubooru\Routes;
use Szurubooru\FormData\LoginFormData;
use Szurubooru\Helpers\InputReader;
use Szurubooru\Router;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PrivilegeService;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;
use Szurubooru\ViewProxies\TokenViewProxy;
use Szurubooru\ViewProxies\UserViewProxy;

class Login extends AbstractRoute
{
    private $authService;
    private $userService;
    private $tokenService;
    private $privilegeService;
    private $inputReader;
    private $userViewProxy;
    private $tokenViewProxy;

    public function __construct(
        AuthService $authService,
        UserService $userService,
        TokenService $tokenService,
        PrivilegeService $privilegeService,
        InputReader $inputReader,
        UserViewProxy $userViewProxy,
        TokenViewProxy $tokenViewProxy)
    {
        $this->authService = $authService;
        $this->userService = $userService;
        $this->tokenService = $tokenService;
        $this->privilegeService = $privilegeService;
        $this->inputReader = $inputReader;
        $this->userViewProxy = $userViewProxy;
        $this->tokenViewProxy = $tokenViewProxy;
    }

    public function getMethods()
    {
        return ['POST', 'PUT'];
    }

    public function getUrl()
    {
        return '/api/login';
    }

    public function work($args)
    {
        if (isset($this->inputReader->userNameOrEmail) && isset($this->inputReader->password))
        {
            $formData = new LoginFormData($this->inputReader);
            $this->authService->loginFromCredentials($formData);

            $user = $this->authService->getLoggedInUser();
            $this->userService->updateUserLastLoginTime($user);
        }
        elseif (isset($this->inputReader->token))
        {
            $token = $this->tokenService->getByName($this->inputReader->token);
            $this->authService->loginFromToken($token);

            $user = $this->authService->getLoggedInUser();
            $isFromCookie = boolval($this->inputReader->isFromCookie);
            if ($isFromCookie)
                $this->userService->updateUserLastLoginTime($user);
        }
        else
        {
            $this->authService->loginAnonymous();
            $user = $this->authService->getLoggedInUser();
        }

        return
        [
            'token' => $this->tokenViewProxy->fromEntity($this->authService->getLoginToken()),
            'user' => $this->userViewProxy->fromEntity($user),
            'privileges' => $this->privilegeService->getCurrentPrivileges(),
        ];
    }
}
