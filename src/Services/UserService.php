<?php
namespace Szurubooru\Services;

class UserService
{
	private $config;
	private $userDao;
	private $passwordService;
	private $emailService;
	private $timeService;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\EmailService $emailService,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->config = $config;
		$this->userDao = $userDao;
		$this->passwordService = $passwordService;
		$this->emailService = $emailService;
		$this->timeService = $timeService;
	}

	public function register(\Szurubooru\FormData\RegistrationFormData $formData)
	{
		$this->validateUserName($formData->name);
		$this->passwordService->validatePassword($formData->password);
		$this->emailService->validateEmail($formData->email);

		if ($this->userDao->getByName($formData->name))
			throw new \DomainException('User with this name already exists.');

		//todo: privilege checking

		$user = new \Szurubooru\Entities\User();
		$user->name = $formData->name;
		$user->email = $formData->email;
		$user->passwordHash = $this->passwordService->getHash($formData->password);
		$user->registrationTime = $this->timeService->getCurrentTime();

		//todo: send activation mail if necessary

		return $this->userDao->save($user);
	}

	public function validateUserName(&$userName)
	{
		$userName = trim($userName);

		if (!$userName)
			throw new \DomainException('User name cannot be empty.');

		$minUserNameLength = intval($this->config->users->minUserNameLength);
		$maxUserNameLength = intval($this->config->users->maxserNameLength);
		if (strlen($userName) < $minUserNameLength)
			throw new \DomainException('User name must have at least ' . $minUserNameLength . ' character(s).');
		if (strlen($userName) < $maxUserNameLength)
			throw new \DomainException('User name must have at most ' . $minUserNameLength . ' character(s).');
	}
}
