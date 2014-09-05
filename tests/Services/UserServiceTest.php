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
	private $timeServiceMock;

	public function setUp()
	{
		$this->configMock = $this->mockConfig();
		$this->validatorMock = $this->mock(\Szurubooru\Validator::class);
		$this->userDaoMock = $this->mock(\Szurubooru\Dao\UserDao::class);
		$this->userSearchService = $this->mock(\Szurubooru\Dao\Services\UserSearchService::class);
		$this->passwordServiceMock = $this->mock(\Szurubooru\Services\PasswordService::class);
		$this->emailServiceMock = $this->mock(\Szurubooru\Services\EmailService::class);
		$this->timeServiceMock = $this->mock(\Szurubooru\Services\TimeService::class);
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

	public function testValidRegistration()
	{
		$formData = new \Szurubooru\FormData\RegistrationFormData;
		$formData->name = 'user';
		$formData->password = 'password';
		$formData->email = 'email';

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
			$this->validatorMock,
			$this->userDaoMock,
			$this->userSearchService,
			$this->passwordServiceMock,
			$this->emailServiceMock,
			$this->timeServiceMock);
	}
}
