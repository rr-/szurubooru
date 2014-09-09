<?php
namespace Szurubooru\Tests\Services;

class AuthServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $passwordServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;
	private $userServiceMock;

	public function setUp()
	{
		$this->configMock = $this->mockConfig();
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->tokenServiceMock = $this->mock(\Szurubooru\Services\TokenService::class);
		$this->userServiceMock = $this->mock(\Szurubooru\Services\UserService::class);
	}

	public function testInvalidPassword()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->method('getHash')->willReturn('unmatchingHash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$this->setExpectedException(\Exception::class, 'Specified password is invalid');
		$authService = $this->getAuthService();
		$formData = new \Szurubooru\FormData\LoginFormData();
		$formData->userNameOrEmail = 'dummy';
		$formData->password = 'godzilla';
		$authService->loginFromCredentials($formData);
	}

	public function testValidCredentials()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 'an unusual database identifier';
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'mummy';
		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->with(
			$testUser->id,
			\Szurubooru\Entities\Token::PURPOSE_LOGIN)->willReturn($testToken);

		$authService = $this->getAuthService();
		$formData = new \Szurubooru\FormData\LoginFormData();
		$formData->userNameOrEmail = 'dummy';
		$formData->password = 'godzilla';
		$authService->loginFromCredentials($formData);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('mummy', $authService->getLoginToken()->name);
	}

	public function testValidCredentialsUnconfirmedEmail()
	{
		$this->configMock->set('security/needEmailActivationToRegister', true);
		$this->passwordServiceMock->method('getHash')->willReturn('hash');

		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'dummy';
		$testUser->passwordHash = 'hash';
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$this->setExpectedException(\Exception::class, 'User didn\'t confirm mail yet');
		$authService = $this->getAuthService();
		$formData = new \Szurubooru\FormData\LoginFormData();
		$formData->userNameOrEmail = 'dummy';
		$formData->password = 'godzilla';
		$authService->loginFromCredentials($formData);

		$this->assertFalse($authService->isLoggedIn());
		$this->assertNull($testUser, $authService->getLoggedInUser());
		$this->assertNull($authService->getLoginToken());
	}

	public function testInvalidToken()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);

		$this->setExpectedException(\Exception::class);
		$authService = $this->getAuthService();
		$testToken = new \Szurubooru\Entities\Token();
		$authService->loginFromToken($testToken);
	}

	public function testValidToken()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 5;
		$testUser->name = 'dummy';
		$this->userServiceMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = $testUser->id;
		$testToken->purpose = \Szurubooru\Entities\Token::PURPOSE_LOGIN;

		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('dummy_token', $authService->getLoginToken()->name);
	}

	public function testValidTokenInvalidPurpose()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = 'whatever';
		$testToken->purpose = null;

		$this->setExpectedException(\Exception::class, 'This token is not a login token');
		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken);

		$this->assertFalse($authService->isLoggedIn());
		$this->assertNull($authService->getLoggedInUser());
		$this->assertNull($authService->getLoginToken());
	}

	public function testValidTokenUnconfirmedEmail()
	{
		$this->configMock->set('security/needEmailActivationToRegister', true);
		$testUser = new \Szurubooru\Entities\User();
		$testUser->id = 5;
		$testUser->name = 'dummy';
		$this->userServiceMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new \Szurubooru\Entities\Token();
		$testToken->name = 'dummy_token';
		$testToken->additionalData = $testUser->id;
		$testToken->purpose = \Szurubooru\Entities\Token::PURPOSE_LOGIN;

		$this->setExpectedException(\Exception::class, 'User didn\'t confirm mail yet');
		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken);

		$this->assertFalse($authService->isLoggedIn());
		$this->assertNull($testUser, $authService->getLoggedInUser());
		$this->assertNull($authService->getLoginToken());
	}

	private function getAuthService()
	{
		return new \Szurubooru\Services\AuthService(
			$this->configMock,
			$this->passwordServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock,
			$this->userServiceMock);
	}
}
