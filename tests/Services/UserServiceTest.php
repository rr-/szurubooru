<?php
namespace Szurubooru\Tests\Services;

final class UserServiceTest extends \Szurubooru\Tests\AbstractTestCase
{
	private $configMock;
	private $userDaoMock;
	private $passwordServiceMock;
	private $emailServiceMock;
	private $timeServiceMock;

	public function setUp()
	{
		$this->configMock = new \Szurubooru\Config;
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->emailServiceMock = $this->mock(\Szurubooru\Services\EmailService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
	}

	public function testValidRegistration()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->name = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		//todo: this shouldn't be needed. refactor validation
		$this->configMock->users = new \StdClass;
		$this->configMock->users->minUserNameLength = 0;
		$this->configMock->users->maxUserNameLength = 50;

		$this->passwordServiceMock->method('getHash')->willReturn('hash');
		$this->timeServiceMock->method('getCurrentTime')->willReturn('now');
		$this->userDaoMock->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->register($formData);

		$this->assertEquals('user', $savedUser->name);
		$this->assertEquals('email', $savedUser->email);
		$this->assertEquals('hash', $savedUser->passwordHash);
		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER, $savedUser->accessRank);
		$this->assertEquals('now', $savedUser->registrationTime);
	}

	public function testAccessRankOfFirstUser()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->name = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		//todo: this shouldn't be needed. refactor validation
		$this->configMock->users = new \StdClass;
		$this->configMock->users->minUserNameLength = 0;
		$this->configMock->users->maxUserNameLength = 50;

		$this->userDaoMock->method('hasAnyUsers')->willReturn(false);
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();
		$savedUser = $userService->register($formData);

		$this->assertEquals(\Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR, $savedUser->accessRank);
	}

	public function testRegistrationWhenUserExists()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->name = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

		//todo: this shouldn't be needed. refactor validation
		$this->configMock->users = new \StdClass;
		$this->configMock->users->minUserNameLength = 0;
		$this->configMock->users->maxUserNameLength = 50;

		$this->userDaoMock->method('hasAnyUsers')->willReturn(true);
		$this->userDaoMock->method('getByName')->willReturn(new \Szurubooru\Entities\User());
		$this->userDaoMock->method('save')->will($this->returnArgument(0));

		$userService = $this->getUserService();

		$this->setExpectedException(\Exception::class, 'User with this name already exists');
		$savedUser = $userService->register($formData);
	}

	private function getUserService()
	{
		return new \Szurubooru\Services\UserService(
			$this->configMock,
			$this->userDaoMock,
			$this->passwordServiceMock,
			$this->emailServiceMock,
			$this->timeServiceMock);
	}
}
