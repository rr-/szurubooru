<?php
namespace Szurubooru\Tests\Services;

final class UserServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $validatorMock;
	private $transactionManagerMock;
	private $userDaoMock;
	private $passwordServiceMock;
	private $emailServiceMock;
	private $fileServiceMock;
	private $thumbnailServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;

	public function setUp()
	{
		parent::setUp();
		$this->configMock = $this->mockConfig();
		$this->transactionManagerMock = $this->mockTransactionManager();
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->emailServiceMock = $this->mock(\Szurubooru\Services\EmailService::class);
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->tokenServiceMock = $this->mock(\Szurubooru\Services\TokenService::class);
	}

	public function testGettingByName()
	{
		$testUser = new \Szurubooru\Entities\User;
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
		$testUser = new \Szurubooru\Entities\User;
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
		$formData = new \Szurubooru\FormData\RegistrationFormData;
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
		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER, $savedUser->getAccessRank());
		$this->assertEquals('now', $savedUser->getRegistrationTime());
		$this->assertTrue($savedUser->isAccountConfirmed());
	}

	public function testValidRegistrationWithMailActivation()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'human@people.gov';

		$this->configMock->set('security/needEmailActivationToRegister', true);
		$this->passwordServiceMock->expects($this->once())->method('getRandomPassword')->willReturn('salt');
		$this->passwordServiceMock->expects($this->once())->method('getHash')->with('password', 'salt')->willReturn('hash');
		$this->timeServiceMock->expects($this->once())->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$testToken = new \Szurubooru\Entities\Token;
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
		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER, $savedUser->getAccessRank());
		$this->assertEquals('now', $savedUser->getRegistrationTime());
		$this->assertFalse($savedUser->isAccountConfirmed());
	}

	public function testAccessRankOfFirstUser()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(false);
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR, $savedUser->getAccessRank());
	}

	public function testRegistrationWhenUserExists()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$otherUser = new \Szurubooru\Entities\User('yes, i exist in database');

		$this->userDaoMock->expects($this->once())->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->expects($this->once())->method('findByName')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$userService = $this->getUserService();

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$savedUser = $userService->createUser($formData);
	}

	public function testUpdatingName()
	{
		$testUser = new \Szurubooru\Entities\User;
		$testUser->setName('wojtek');

		$formData = new \Szurubooru\FormData\UserEditFormData;
		$formData->userName = 'sebastian';

		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertEquals('sebastian', $savedUser->getName());
	}

	public function testUpdatingNameToExisting()
	{
		$testUser = new \Szurubooru\Entities\User;
		$testUser->setName('wojtek');

		$formData = new \Szurubooru\FormData\UserEditFormData;
		$formData->userName = 'sebastian';

		$otherUser = new \Szurubooru\Entities\User('yes, i exist in database');
		$this->userDaoMock->expects($this->once())->method('findByName')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
	}

	public function testUpdatingEmailWithoutConfirmation()
	{
		$testUser = new \Szurubooru\Entities\User;
		$this->configMock->set('security/needEmailActivationToRegister', false);

		$formData = new \Szurubooru\FormData\UserEditFormData;
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
		$testUser = new \Szurubooru\Entities\User;
		$this->configMock->set('security/needEmailActivationToRegister', true);

		$formData = new \Szurubooru\FormData\UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->willReturn(new \Szurubooru\Entities\Token());
		$this->userDaoMock->expects($this->once())->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->updateUser($testUser, $formData);
		$this->assertNull($savedUser->getEmail());
		$this->assertEquals('hikari@geofront.gov', $savedUser->getEmailUnconfirmed());
		$this->assertFalse($savedUser->isAccountConfirmed());
	}

	public function testUpdatingEmailWithConfirmationToExisting()
	{
		$testUser = new \Szurubooru\Entities\User;
		$this->configMock->set('security/needEmailActivationToRegister', true);

		$formData = new \Szurubooru\FormData\UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$otherUser = new \Szurubooru\Entities\User('yes, i exist in database');
		$this->tokenServiceMock->expects($this->never())->method('createAndSaveToken');
		$this->userDaoMock->expects($this->once())->method('findByEmail')->willReturn($otherUser);
		$this->userDaoMock->expects($this->never())->method('save');

		$this->setExpectedException(\Exception::class, 'User with this e-mail already exists');
		$userService = $this->getUserService();
		$userService->updateUser($testUser, $formData);
	}

	public function testUpdatingEmailToAlreadyConfirmed()
	{
		$testUser = new \Szurubooru\Entities\User('yep, still me');
		$testUser->setEmail('hikari@geofront.gov');
		$testUser->setAccountConfirmed(true);
		$testUser->setEmailUnconfirmed('coolcat32@sakura.ne.jp');

		$formData = new \Szurubooru\FormData\UserEditFormData;
		$formData->email = 'hikari@geofront.gov';

		$otherUser = new \Szurubooru\Entities\User('yep, still me');
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
		return new \Szurubooru\Services\UserService(
			$this->configMock,
			$this->validatorMock,
			$this->transactionManagerMock,
			$this->userDaoMock,
			$this->passwordServiceMock,
			$this->emailServiceMock,
			$this->fileServiceMock,
			$this->thumbnailServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock);
	}
}
