<?php
namespace Szurubooru\Tests\Services;

class AuthServiceTest extends \PHPUnit_Framework_TestCase
{
	public function testInvalidUser()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$this->setExpectedException(\InvalidArgumentException::class, 'User not found');

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testInvalidPassword()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$passwordServiceMock->method('getHash')->willReturn('unmatchingHash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $tokenDaoMock, $userDaoMock);
		$this->setExpectedException(\InvalidArgumentException::class, 'Specified password is invalid');
		$authService->loginFromCredentials('dummy', 'godzilla');
	}

	public function testValidCredentials()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
		$tokenDaoMock = $this->getTokenDaoMock();
		$userDaoMock = $this->getUserDaoMock();

		$tokenDaoMock->expects($this->once())->method('save');
		$passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromCredentials('dummy', 'godzilla');

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
	}

	public function testValidToken()
	{
		$passwordServiceMock = $this->getPasswordServiceMock();
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

		$authService = new \Szurubooru\Services\AuthService($passwordServiceMock, $tokenDaoMock, $userDaoMock);
		$authService->loginFromToken($testToken->name);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
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
}
