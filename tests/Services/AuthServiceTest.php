<?php
namespace Szurubooru\Tests\Services;

class AuthServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $validatorMock;
	private $passwordServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;
	private $userServiceMock;

	public function setUp()
	{
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->tokenServiceMock = $this->mock(\Szurubooru\Services\TokenService::class);
		$this->userServiceMock = $this->mock(\Szurubooru\Services\UserService::class);
	}

	public function testInvalidPassword()
	{
		$this->passwordServiceMock->method('getHash')->willReturn('unmatchingHash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userServiceMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = $this->getAuthService();
		$this->setExpectedException(\Exception::class, 'Specified password is invalid');
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testValidCredentials()
	{
		$this->passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userServiceMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'mummy';
		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->with(
			$testUser,
			\Szurubooru\Entities\Token::PURPOSE_LOGIN)->willReturn($testToken);

		$authService = $this->getAuthService();
		$authService->loginFromCredentials('dummy', 'godzilla');

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('mummy', $authService->getLoginToken()->name);
	}

	public function testInvalidToken()
	{
		$this->tokenServiceMock->expects($this->once())->method('getByName')->willReturn(null);

		$this->setExpectedException(\Exception::class);
		$authService = $this->getAuthService();
		$authService->loginFromToken('');
	}

	public function testValidToken()
	{
		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 5;
		$testUser->name = 'dummy';
		$this->userServiceMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = $testUser->id;
		$testToken->purpose = \Szurubooru\Entities\Token::PURPOSE_LOGIN;
		$this->tokenServiceMock->expects($this->once())->method('getByName')->willReturn($testToken);

		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken->name);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('dummy_token', $authService->getLoginToken()->name);
	}

	private function getAuthService()
	{
		return new \Szurubooru\Services\AuthService(
			$this->validatorMock,
			$this->passwordServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock,
			$this->userServiceMock);
	}
}
