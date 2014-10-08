<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Entities\Token;
use Szurubooru\Entities\User;
use Szurubooru\FormData\LoginFormData;
use Szurubooru\Services\AuthService;
use Szurubooru\Services\PasswordService;
use Szurubooru\Services\TimeService;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;
use Szurubooru\Tests\AbstractTestCase;

final class AuthServiceTest extends AbstractTestCase
{
	private $configMock;
	private $passwordServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;
	private $userServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
		$this->passwordServiceMock = $this->mock(PasswordService::class);
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->tokenServiceMock = $this->mock(TokenService::class);
		$this->userServiceMock = $this->mock(UserService::class);
	}

	public function testInvalidPassword()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->expects($this->once())->method('isHashValid')->with('godzilla', 'salt', 'hash')->willReturn(false);

		$testUser = new User();
		$testUser->setName('dummy');
		$testUser->setPasswordHash('hash');
		$testUser->setPasswordSalt('salt');
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$this->setExpectedException(\Exception::class, 'Specified password is invalid');
		$authService = $this->getAuthService();
		$formData = new LoginFormData();
		$formData->userNameOrEmail = 'dummy';
		$formData->password = 'godzilla';
		$authService->loginFromCredentials($formData);
	}

	public function testValidCredentials()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->expects($this->once())->method('isHashValid')->with('godzilla', 'salt', 'hash')->willReturn(true);

		$testUser = new User('an unusual database identifier');
		$testUser->setName('dummy');
		$testUser->setPasswordHash('hash');
		$testUser->setPasswordSalt('salt');
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$testToken = new Token();
		$testToken->setName('mummy');
		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->with(
			$testUser->getId(),
			Token::PURPOSE_LOGIN)->willReturn($testToken);

		$authService = $this->getAuthService();
		$formData = new LoginFormData();
		$formData->userNameOrEmail = 'dummy';
		$formData->password = 'godzilla';
		$authService->loginFromCredentials($formData);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('mummy', $authService->getLoginToken()->getName());
	}

	public function testValidCredentialsUnconfirmedEmail()
	{
		$this->configMock->set('security/needEmailActivationToRegister', true);
		$this->passwordServiceMock->expects($this->never())->method('isHashValid')->willReturn('hash');

		$testUser = new User();
		$testUser->setName('dummy');
		$testUser->setPasswordHash('hash');
		$this->userServiceMock->expects($this->once())->method('getByNameOrEmail')->willReturn($testUser);

		$this->setExpectedException(\Exception::class, 'User didn\'t confirm account yet');
		$authService = $this->getAuthService();
		$formData = new LoginFormData();
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
		$testToken = new Token();
		$authService->loginFromToken($testToken);
	}

	public function testValidToken()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$testUser = new User(5);
		$testUser->setName('dummy');
		$this->userServiceMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new Token();
		$testToken->setName('dummy_token');
		$testToken->setAdditionalData($testUser->getId());
		$testToken->setPurpose(Token::PURPOSE_LOGIN);

		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken);

		$this->assertTrue($authService->isLoggedIn());
		$this->assertEquals($testUser, $authService->getLoggedInUser());
		$this->assertNotNull($authService->getLoginToken());
		$this->assertEquals('dummy_token', $authService->getLoginToken()->getName());
	}

	public function testValidTokenInvalidPurpose()
	{
		$this->configMock->set('security/needEmailActivationToRegister', false);
		$testToken = new Token();
		$testToken->setName('dummy_token');
		$testToken->setAdditionalData('whatever');
		$testToken->setPurpose(null);

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
		$testUser = new User(5);
		$testUser->setName('dummy');
		$this->userServiceMock->expects($this->once())->method('getById')->willReturn($testUser);

		$testToken = new Token();
		$testToken->setName('dummy_token');
		$testToken->setAdditionalData($testUser->getId());
		$testToken->setPurpose(Token::PURPOSE_LOGIN);

		$this->setExpectedException(\Exception::class, 'User didn\'t confirm account yet');
		$authService = $this->getAuthService();
		$authService->loginFromToken($testToken);

		$this->assertFalse($authService->isLoggedIn());
		$this->assertNull($testUser, $authService->getLoggedInUser());
		$this->assertNull($authService->getLoginToken());
	}

	private function getAuthService()
	{
		return new AuthService(
			$this->configMock,
			$this->passwordServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock,
			$this->userServiceMock);
	}
}
