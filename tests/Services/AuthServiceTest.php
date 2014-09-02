<?php
namespace Szurubooru\Tests\Services;

class AuthServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $validatorMock;
	private $passwordServiceMock;
	private $timeServiceMock;
	private $tokenDaoMock;
	private $userDaoMock;

	public function setUp()
	{
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->tokenDaoMock = $this->mock(\Szurubooru\Dao\TokenDao::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
	}

	public function testInvalidUser()
	{
		$this->setExpectedException(\InvalidArgumentException::class, 'User not found');

		$authService = $this->getAuthService();
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testInvalidPassword()
	{
		$this->passwordServiceMock->method('getHash')->willReturn('unmatchingHash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = $this->getAuthService();
		$this->setExpectedException(\InvalidArgumentException::class, 'Specified password is invalid');
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testValidCredentials()
	{
		$this->tokenDaoMock->expects($this->once())->method('save');
		$this->passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = $this->getAuthService();
		$authService->loginFromCredentials('dummy', 'godzilla');

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertNotNull($authService->getLoginToken()->name);
	}

	public function testInvalidToken()
	{
		$this->tokenDaoMock->expects($this->once())->method('getByName')->willReturn(null);

		$this->setExpectedException(\Exception::class);
		$authService = $this->getAuthService();
		$authService->loginFromToken('');
	}

	public function testValidToken()
	{
		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 5;
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userDaoMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = $testUser->id;
		$this->tokenDaoMock->expects($this->once())->method('getByName')->willReturn($testToken);

		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken->name);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertNotNull($authService->getLoginToken()->name);
	}

	private function getAuthService()
	{
		return new \Szurubooru\Services\AuthService(
			$this->validatorMock,
			$this->passwordServiceMock,
			$this->timeServiceMock,
			$this->tokenDaoMock,
			$this->userDaoMock);
	}
}
