<?php
namespace Szurubooru\Tests\Services;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Token;
use Szurubooru\Entities\User;
use Szurubooru\FormData\RegistrationFormData;
use Szurubooru\FormData\UserEditFormData;
use Szurubooru\Services\EmailService;
use Szurubooru\Services\PasswordService;
use Szurubooru\Services\TimeService;
use Szurubooru\Services\TokenService;
use Szurubooru\Services\UserService;
use Szurubooru\Tests\AbstractTestCase;
use Szurubooru\Validator;

final class UserServiceTest extends AbstractTestCase
{
	private $configMock;
	private $validatorMock;
	private $transactionManagerMock;
	private $userDaoMock;
	private $passwordServiceMock;
	private $emailServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->validatorMock = $this->mock(Validator::class);
		$this->userDaoMock = $this->mock(UserDao::class);
		$this->passwordServiceMock = $this->mock(PasswordService::class);
		$this->emailServiceMock = $this->mock(EmailService::class);
		$this->timeServiceMock = $this->mock(TimeService::class);
		$this->tokenServiceMock = $this->mock(TokenService::class);
	}

	public function testGettingByName()
	{
		$testUser = new User;
		$testUser->setName('godzilla');
		$this->userDaoMock->expects($this->once())->method('findByName')->willReturn($testUser);
		$userService = $this->getUserService();
		$expected = $testUser;
		$actual = $userService->getByName('godzilla');
		$this->assertEquals($expected, $actual);
	}

	public function testGettingByNameNonExistentUsers()
	{
		$this->setExpectedException(\Exception::class, 'User with name "godzilla" was not found.');
		$userService = $this->getUserService();
		$userService->getByName('godzilla');
	}

	public function testGettingById()
	{
		$testUser = new User;
		$testUser->setName('godzilla');
		$this->userDaoMock->expects($this->once())->method('findById')->willReturn($testUser);
		$userService = $this->getUserService();
		$expected = $testUser;
		$actual = $userService->getById('godzilla');
		$this->assertEquals($expected, $actual);
	}

	public function testGettingByIdNonExistentUsers()
	{
		$this->setExpectedException(\Exception::class, 'User with id "godzilla" was not found.');
		$userService = $this->getUserService();
		$userService->getById('godzilla');
	}

	public function testValidRegistrationWithoutMailActivation()
	{
		$formData = new RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'human@people.gov';

		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->expects($this->once())->method('getRandomPassword')->willReturn('salt');
		$this->passwordServiceMock->expects($this->once())->method('getHash')->with('password', 'salt')->willReturn('hash');
		$this->timeServiceMock->expects($this->once())->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));
		$this->emailServiceMock->expects($this->never())->method('sendActivationEmail');

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals('user', $savedUser->getName());
		$this->assertEquals('human@people.gov', $savedUser->getEmail());
		$this->assertNull($savedUser->getEmailUnconfirmed());
		$this->assertEquals('hash', $savedUser->getPasswordHash());
		$this->assertEquals(User::ACCESS_RANK_REGULAR_USER, $savedUser->getAccessRank());
		$this->assertEquals('now', $savedUser->getRegistrationTime());
		$this->assertTrue($savedUser->isAccountConfirmed());
	}

	public function testValidRegistrationWithMailActivation()
	{
		$formData = new RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'human@people.gov';

		$this->configMock->set('security/needEmailActivationToRegister', true);
		$this->passwordServiceMock->expects($this->once())->method('getRandomPassword')->willReturn('salt');
		$this->passwordServiceMock->expects($this->once())->method('getHash')->with('password', 'salt')->willReturn('hash');
		$this->timeServiceMock->expects($this->once())->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$testToken = new Token;
		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->willReturn($testToken);
		$this->emailServiceMock->expects($this->once())->method('sendActivationEmail')->with(
			$this->anything(),
			$testToken);

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals('user', $savedUser->getName());
		$this->assertNull($savedUser->getEmail());
		$this->assertEquals('human@people.gov', $savedUser->getEmailUnconfirmed());
		$this->assertEquals('hash', $savedUser->getPasswordHash());
		$this->assertEquals(User::ACCESS_RANK_REGULAR_USER, $savedUser->getAccessRank());
		$this->assertEquals('now', $savedUser->getRegistrationTime());
		$this->assertFalse($savedUser->isAccountConfirmed());
	}

	public function testAccessRankOfFirstUser()
	{
		$formData = new RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(false);
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals(User::ACCESS_RANK_ADMINISTRATOR, $savedUser->getAccessRank());
	}

	public function testRegistrationWhenUserExists()
	{
		$formData = new RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$otherUser = new User('yes, i exist in database');

		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->expects($this->once())->method('findByName')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$userService = $this->getUserService();

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$savedUser = $userService->createUser($formData);
	}

	public function testUpdatingName()
	{
		$testUser = new User;
		$testUser->setName('wojtek');

		$formData = new UserEditFormData;
		$formData->userName = 'sebastian';

		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertEquals('sebastian', $savedUser->getName());
	}

	public function testUpdatingNameToExisting()
	{
		$testUser = new User;
		$testUser->setName('wojtek');

		$formData = new UserEditFormData;
		$formData->userName = 'sebastian';

		$otherUser = new User('yes, i exist in database');
		$this->userDaoMock->expects($this->once())->method('findByName')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
	}

	public function testUpdatingEmailWithoutConfirmation()
	{
		$testUser = new User;
		$this->configMock->set('security/needEmailActivationToRegister', false);

		$formData = new UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertEquals('hikari@geofront.gov', $savedUser->getEmail());
		$this->assertNull($savedUser->getEmailUnconfirmed());
		$this->assertTrue($savedUser->isAccountConfirmed());
	}

	public function testUpdatingEmailWithConfirmation()
	{
		$testUser = new User;
		$this->configMock->set('security/needEmailActivationToRegister', true);

		$formData = new UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->willReturn(new Token());
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertNull($savedUser->getEmail());
		$this->assertEquals('hikari@geofront.gov', $savedUser->getEmailUnconfirmed());
		$this->assertFalse($savedUser->isAccountConfirmed());
	}

	public function testUpdatingEmailWithConfirmationToExisting()
	{
		$testUser = new User;
		$this->configMock->set('security/needEmailActivationToRegister', true);

		$formData = new UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$otherUser = new User('yes, i exist in database');
		$this->tokenServiceMock->expects($this->never())->method('createAndSaveToken');
		$this->userDaoMock->expects($this->once())->method('findByEmail')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$this->setExpectedException(\Exception::class, 'User with this e-mail already exists');
		$userService = $this->getUserService();
		$userService->updateUser($testUser, $formData);
	}

	public function testUpdatingEmailToAlreadyConfirmed()
	{
		$testUser = new User('yep, still me');
		$testUser->setEmail('hikari@geofront.gov');
		$testUser->setAccountConfirmed(true);
		$testUser->setEmailUnconfirmed('coolcat32@sakura.ne.jp');

		$formData = new UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$otherUser = new User('yep, still me');
		$this->tokenServiceMock->expects($this->never())->method('createAndSaveToken');
		$this->userDaoMock->expects($this->never())->method('findByEmail');
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertEquals('hikari@geofront.gov', $savedUser->getEmail());
		$this->assertNull($savedUser->getEmailUnconfirmed());
		$this->assertTrue($savedUser->isAccountConfirmed());
	}

	private function getUserService()
	{
		return new UserService(
			$this->configMock,
			$this->validatorMock,
			$this->transactionManagerMock,
			$this->userDaoMock,
			$this->passwordServiceMock,
			$this->emailServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock);
	}
}
