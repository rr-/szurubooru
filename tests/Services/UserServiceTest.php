<?php
namespace Szurubooru\Tests\Services;

final class UserServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $validatorMock;
	private $userDaoMock;
	private $userSearchServiceMock;
	private $passwordServiceMock;
	private $emailServiceMock;
	private $fileServiceMock;
	private $thumbnailServiceMock;
	private $timeServiceMock;
	private $tokenServiceMock;

	public function setUp()
	{
		$this->configMock = $this->mockConfig();
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->userSearchService = $this->mock(\Szurubooru\Dao\Services\UserSearchService::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->emailServiceMock = $this->mock(\Szurubooru\Services\EmailService::class);
		$this->fileServiceMock = $this->mock(\Szurubooru\Services\FileService::class);
		$this->thumbnailServiceMock = $this->mock(\Szurubooru\Services\ThumbnailService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
		$this->tokenServiceMock = $this->mock(\Szurubooru\Services\TokenService::class);
	}

	public function testGettingByName()
	{
		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'godzilla';
		$this->userDaoMock->expects($this->once())->method('getByName')->willReturn($testUser);
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
		$testUser = new \Szurubooru\Entities\User();
		$testUser->name = 'godzilla';
		$this->userDaoMock->expects($this->once())->method('getById')->willReturn($testUser);
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

	public function testGettingFilteredUsers()
	{
		$mockUser = new \Szurubooru\Entities\User();
		$mockUser->name = 'user';
		$expected = [$mockUser];
		$this->userSearchService->method('getFiltered')->willReturn($expected);

		$this->configMock->set('users/usersPerPage', 1);
		$searchFormData = new \Szurubooru\FormData\SearchFormData;
		$searchFormData->query = '';
		$searchFormData->order = 'joined';
		$searchFormData->page = 2;

		$userService = $this->getUserService();
		$actual = $userService->getFiltered($searchFormData);
		$this->assertEquals($expected, $actual);
	}

	public function testValidRegistrationWithoutMailActivation()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'human@people.gov';

		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->passwordServiceMock->method('getHash')->willReturn('hash');
		$this->timeServiceMock->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->method('save')->will($this->returnArgument(0));
		$this->emailServiceMock->expects($this->never())->method('sendActivationEmail');

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals('user', $savedUser->name);
		$this->assertEquals('human@people.gov', $savedUser->email);
		$this->assertNull($savedUser->emailUnconfirmed);
		$this->assertEquals('hash', $savedUser->passwordHash);
		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER, $savedUser->accessRank);
		$this->assertEquals('now', $savedUser->registrationTime);
	}

	public function testValidRegistrationWithMailActivation()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'human@people.gov';

		$this->configMock->set('security/needEmailActivationToRegister', true);
		$this->passwordServiceMock->method('getHash')->willReturn('hash');
		$this->timeServiceMock->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$testToken = new \Szurubooru\Entities\Token();
		$this->tokenServiceMock->expects($this->once())->method('createAndSaveToken')->willReturn($testToken);
		$this->emailServiceMock->expects($this->once())->method('sendActivationEmail')->with(
			$this->anything(),
			$testToken);

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals('user', $savedUser->name);
		$this->assertNull($savedUser->email);
		$this->assertEquals('human@people.gov', $savedUser->emailUnconfirmed);
		$this->assertEquals('hash', $savedUser->passwordHash);
		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER, $savedUser->accessRank);
		$this->assertEquals('now', $savedUser->registrationTime);
	}

	public function testAccessRankOfFirstUser()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$this->configMock->set('security/needEmailActivationToRegister', false);
		$this->userDaoMock->method('hasAnyUsers')->willReturn(false);
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->createUser($formData);

		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR, $savedUser->accessRank);
	}

	public function testRegistrationWhenUserExists()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->userName = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		$this->userDaoMock->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->method('getByName')->willReturn(new \Szurubooru\Entities\User());
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$savedUser = $userService->createUser($formData);
	}

	private function getUserService()
	{
		return new \Szurubooru\Services\UserService(
			$this->configMock,
			$this->validatorMock,
			$this->userDaoMock,
			$this->userSearchService,
			$this->passwordServiceMock,
			$this->emailServiceMock,
			$this->fileServiceMock,
			$this->thumbnailServiceMock,
			$this->timeServiceMock,
			$this->tokenServiceMock);
	}
}
