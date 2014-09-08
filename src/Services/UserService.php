<?php
namespace Szurubooru\Services;

class UserService
{
	private $config;
	private $validator;
	private $userDao;
	private $userSearchService;
	private $passwordService;
	private $emailService;
	private $fileService;
	private $timeService;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\Services\UserSearchService $userSearchService,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\EmailService $emailService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\TimeService $timeService)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->userDao = $userDao;
		$this->userSearchService = $userSearchService;
		$this->passwordService = $passwordService;
		$this->emailService = $emailService;
		$this->fileService = $fileService;
		$this->timeService = $timeService;
	}

	public function getByName($userName)
	{
		$user = $this->userDao->getByName($userName);
		if (!$user)
			throw new \InvalidArgumentException('User with name "' . $userName . '" was not found.');
		return $user;
	}

	public function getById($userId)
	{
		$user = $this->userDao->getById($userId);
		if (!$user)
			throw new \InvalidArgumentException('User with id "' . $userId . '" was not found.');
		return $user;
	}

	public function getFiltered(\Szurubooru\FormData\SearchFormData $formData)
	{
		$pageSize = intval($this->config->users->usersPerPage);
		$this->validator->validateNumber($formData->page);
		$searchFilter = new \Szurubooru\Dao\SearchFilter($pageSize, $formData);
		return $this->userSearchService->getFiltered($searchFilter);
	}

	public function createUser(\Szurubooru\FormData\RegistrationFormData $formData)
	{
		$this->validator->validateUserName($formData->userName);
		$this->validator->validatePassword($formData->password);
		$this->validator->validateEmail($formData->email);

		if ($this->userDao->getByName($formData->userName))
			throw new \DomainException('User with this name already exists.');

		$user = new \Szurubooru\Entities\User();
		$user->name = $formData->userName;
		$user->email = $formData->email;
		$user->passwordHash = $this->passwordService->getHash($formData->password);
		$user->accessRank = $this->userDao->hasAnyUsers()
			? \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER
			: \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR;
		$user->registrationTime = $this->timeService->getCurrentTime();
		$user->lastLoginTime = null;
		$user->avatarStyle = \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR;

		$this->sendActivationMailIfNeeded($user);

		return $this->userDao->save($user);
	}

	public function updateUser($userName, \Szurubooru\FormData\UserEditFormData $formData)
	{
		$user = $this->getByName($userName);

		if ($formData->avatarStyle !== null)
		{
			$user->avatarStyle = \Szurubooru\Helpers\EnumHelper::avatarStyleFromString($formData->avatarStyle);
			if ($formData->avatarContent)
				$this->fileService->saveFromBase64($formData->avatarContent, $this->getCustomAvatarSourcePath($user));
		}

		if ($formData->userName !== null and $formData->userName != $user->name)
		{
			$this->validator->validateUserName($formData->userName);
			if ($this->userDao->getByName($formData->userName))
				throw new \DomainException('User with this name already exists.');

			$user->name = $formData->userName;
		}

		if ($formData->password !== null)
		{
			$this->validator->validatePassword($formData->password);
			$user->passwordHash = $this->passwordService->getHash($formData->password);
		}

		if ($formData->email !== null)
		{
			$this->validator->validateEmail($formData->email);
			$user->email = $formData->email;
		}

		if ($formData->accessRank !== null)
		{
			$user->accessRank = \Szurubooru\Helpers\EnumHelper::accessRankFromString($formData->accessRank);
		}

		if ($formData->browsingSettings !== null)
		{
			if (!is_string($formData->browsingSettings))
				throw new \InvalidArgumentException('Browsing settings must be stringified JSON.');
			if (strlen($formData->browsingSettings) > 2000)
				throw new \InvalidArgumentException('Stringified browsing settings can have at most 2000 characters.');
			$user->browsingSettings = $formData->browsingSettings;
		}

		if ($formData->email !== null)
			$this->sendActivationMailIfNeeded($user);

		return $this->userDao->save($user);
	}

	public function deleteUserByName($userName)
	{
		$user = $this->getByName($userName);
		$this->userDao->deleteByName($userName);
		$this->fileService->delete($this->getCustomAvatarSourcePath($user));
		return true;
	}

	public function getCustomAvatarSourcePath(\Szurubooru\Entities\User $user)
	{
		return 'avatars' . DIRECTORY_SEPARATOR . $user->id;
	}

	public function getBlankAvatarSourcePath()
	{
		return 'avatars' . DIRECTORY_SEPARATOR . 'blank.png';
	}

	public function updateUserLastLoginTime(\Szurubooru\Entities\User $user)
	{
		$user->lastLoginTime = $this->timeService->getCurrentTime();
		$this->userDao->save($user);
	}

	private function sendActivationMailIfNeeded(\Szurubooru\Entities\User &$user)
	{
		//todo
	}
}
