<?php
namespace Szurubooru\Tests\Services;

class AuthServiceTest extends \PHPUnit_Framework_TestCase
{
	public function testInvalidUser()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$timeServiceMock = $this->getTimeServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$this->setExpectedException(\InvalidArgumentException::class, 'User not found');

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $timeServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testInvalidPassword()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$timeServiceMock = $this->getTimeServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$passwordServiceMock->method('getHash')->willReturn('unmatchingHash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $timeServiceMock, $tokenDaoMock, $userDaoMock);
		$this->setExpectedException(\InvalidArgumentException::class, 'Specified password is invalid');
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testValidCredentials()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$timeServiceMock = $this->getTimeServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$tokenDaoMock->expects($this->once())->method('save');
		$passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $timeServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromCredentials('dummy', 'godzilla');

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertNotNull($authService->getLoginToken()->name);
	}

	public function testInvalidToken()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$timeServiceMock = $this->getTimeServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$tokenDaoMock->expects($this->once())->method('getByName')->willReturn(null);

		$this->setExpectedException(\Exception::class);
		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $timeServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromToken('');
	}

	public function testValidToken()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$timeServiceMock = $this->getTimeServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 5;
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$userDaoMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = $testUser->id;
		$tokenDaoMock->expects($this->once())->method('getByName')->willReturn($testToken);

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $timeServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromToken($testToken->name);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertNotNull($authService->getLoginToken()->name);
	}

	private function getTokenDaoMock()
	{
		return $this->getMockBuilder(\Szurubooru\Dao\TokenDao::class)->disableOriginalConstructor()->getMock();
	}

	private function getUserDaoMock()
	{
		return $this->getMockBuilder(\Szurubooru\Dao\UserDao::class)->disableOriginalConstructor()->getMock();
	}

	private function getPasswordServiceMock()
	{
		return $this->getMockBuilder(\Szurubooru\Services\PasswordService::class)->disableOriginalConstructor()->getMock();
	}

	private function getTimeServiceMock()
	{
		return $this->getMockBuilder(\Szurubooru\Services\TimeService::class)->disableOriginalConstructor()->getMock();
	}
}
