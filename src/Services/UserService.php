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
	private $tokenService;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Dao\Services\UserSearchService $userSearchService,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\EmailService $emailService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\TokenService $tokenService)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->userDao = $userDao;
		$this->userSearchService = $userSearchService;
		$this->passwordService = $passwordService;
		$this->emailService = $emailService;
		$this->fileService = $fileService;
		$this->timeService = $timeService;
		$this->tokenService = $tokenService;
	}

	public function getByNameOrEmail($userNameOrEmail, $allowUnconfirmed = false)
	{
		$user = $this->userDao->getByName($userNameOrEmail);
		if ($user)
			return $user;

		$user = $this->userDao->getByEmail($userNameOrEmail, $allowUnconfirmed);
		if ($user)
			return $user;

		throw new \InvalidArgumentException('User "' . $userNameOrEmail . '" was not found.');
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
		$this->validator->validateNumber($formData->pageNumber);
		$searchFilter = new \Szurubooru\Dao\SearchFilter($pageSize, $formData);
		return $this->userSearchService->getFiltered($searchFilter);
	}

	public function createUser(\Szurubooru\FormData\RegistrationFormData $formData)
	{
		$this->validator->validateUserName($formData->userName);
		$this->validator->validatePassword($formData->password);
		$this->validator->validateEmail($formData->email);

		if ($formData->email and $this->userDao->getByEmail($formData->email))
			throw new \DomainException('User with this e-mail already exists.');

		if ($this->userDao->getByName($formData->userName))
			throw new \DomainException('User with this name already exists.');

		$user = new \Szurubooru\Entities\User();
		$user->name = $formData->userName;
		$user->emailUnconfirmed = $formData->email;
		$user->passwordHash = $this->passwordService->getHash($formData->password);
		$user->accessRank = $this->userDao->hasAnyUsers()
			? \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER
			: \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR;
		$user->registrationTime = $this->timeService->getCurrentTime();
		$user->lastLoginTime = null;
		$user->avatarStyle = \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR;

		$user = $this->sendActivationEmailIfNeeded($user);

		return $this->userDao->save($user);
	}

	public function updateUser(\Szurubooru\Entities\User $user, \Szurubooru\FormData\UserEditFormData $formData)
	{
		if ($formData->avatarStyle !== null)
		{
			$user->avatarStyle = \Szurubooru\Helpers\EnumHelper::avatarStyleFromString($formData->avatarStyle);
			if ($formData->avatarContent)
				$this->fileService->saveFromBase64($formData->avatarContent, $this->getCustomAvatarSourcePath($user));
		}

		if ($formData->userName !== null and $formData->userName !== $user->name)
		{
			$this->validator->validateUserName($formData->userName);
			$userWithThisEmail = $this->userDao->getByName($formData->userName);
			if ($userWithThisEmail and $userWithThisEmail->id !== $user->id)
				throw new \DomainException('User with this name already exists.');

			$user->name = $formData->userName;
		}

		if ($formData->password !== null)
		{
			$this->validator->validatePassword($formData->password);
			$user->passwordHash = $this->passwordService->getHash($formData->password);
		}

		if ($formData->email !== null and $formData->email !== $user->email)
		{
			$this->validator->validateEmail($formData->email);
			if ($this->userDao->getByEmail($formData->email))
				throw new \DomainException('User with this e-mail already exists.');

			$user->emailUnconfirmed = $formData->email;
			$user = $this->sendActivationEmailIfNeeded($user);
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

		return $this->userDao->save($user);
	}

	public function deleteUser(\Szurubooru\Entities\User $user)
	{
		$this->userDao->deleteById($user->id);
		$this->fileService->delete($this->getCustomAvatarSourcePath($user));
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

	public function sendPasswordResetEmail(\Szurubooru\Entities\User $user)
	{
		$token = $this->tokenService->createAndSaveToken($user->name, \Szurubooru\Entities\Token::PURPOSE_PASSWORD_RESET);
		$this->emailService->sendPasswordResetEmail($user, $token);
	}

	public function finishPasswordReset($tokenName)
	{
		$token = $this->tokenService->getByName($tokenName);
		if ($token->purpose !== \Szurubooru\Entities\Token::PURPOSE_PASSWORD_RESET)
			throw new \Exception('This token is not a password reset token.');

		$user = $this->getByName($token->additionalData);
		$newPassword = $this->passwordService->getRandomPassword();
		$user->passwordHash = $this->passwordService->getHash($newPassword);
		$this->userDao->save($user);
		$this->tokenService->invalidateByName($token->name);
		return $newPassword;
	}

	public function sendActivationEmail(\Szurubooru\Entities\User $user)
	{
		$token = $this->tokenService->createAndSaveToken($user->name, \Szurubooru\Entities\Token::PURPOSE_ACTIVATE);
		$this->emailService->sendActivationEmail($user, $token);
	}

	public function finishActivation($tokenName)
	{
		$token = $this->tokenService->getByName($tokenName);
		if ($token->purpose !== \Szurubooru\Entities\Token::PURPOSE_ACTIVATE)
			throw new \Exception('This token is not an activation token.');

		$user = $this->getByName($token->additionalData);
		$user = $this->confirmEmail($user);
		$this->userDao->save($user);
		$this->tokenService->invalidateByName($token->name);
	}

	private function sendActivationEmailIfNeeded(\Szurubooru\Entities\User $user)
	{
		if ($user->accessRank === \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR or !$this->config->security->needEmailActivationToRegister)
		{
			$user = $this->confirmEmail($user);
		}
		else
		{
			$this->sendActivationEmail($user);
		}
		return $user;
	}

	private function confirmEmail(\Szurubooru\Entities\User $user)
	{
		//security issue:
		//1. two users set their unconfirmed mail to godzilla@empire.gov
		//2. activation mail is sent to both of them
		//3. first user confirms, ok
		//4. second user confirms, ok
		//5. two users share the same mail --> problem.
		//by checking here again for users with such mail, this problem is solved with first-come first-serve approach:
		//whoever confirms e-mail first, wins.
		if ($this->userDao->getByEmail($user->emailUnconfirmed))
			throw new \DomainException('This e-mail was already confirmed by someone else in the meantime.');

		$user->email = $user->emailUnconfirmed;
		$user->emailUnconfirmed = null;
		return $user;
	}
}
