<?php
namespace Szurubooru\Services;

class UserService
{
	private $config;
	private $validator;
	private $transactionManager;
	private $userDao;
	private $passwordService;
	private $emailService;
	private $fileService;
	private $thumbnailService;
	private $timeService;
	private $tokenService;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Validator $validator,
		\Szurubooru\Dao\TransactionManager $transactionManager,
		\Szurubooru\Dao\UserDao $userDao,
		\Szurubooru\Services\PasswordService $passwordService,
		\Szurubooru\Services\EmailService $emailService,
		\Szurubooru\Services\FileService $fileService,
		\Szurubooru\Services\ThumbnailService $thumbnailService,
		\Szurubooru\Services\TimeService $timeService,
		\Szurubooru\Services\TokenService $tokenService)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->userDao = $userDao;
		$this->passwordService = $passwordService;
		$this->emailService = $emailService;
		$this->fileService = $fileService;
		$this->thumbnailService = $thumbnailService;
		$this->timeService = $timeService;
		$this->tokenService = $tokenService;
	}

	public function getByNameOrEmail($userNameOrEmail, $allowUnconfirmed = false)
	{
		$transactionFunc = function() use ($userNameOrEmail, $allowUnconfirmed)
		{
			$user = $this->userDao->findByName($userNameOrEmail);
			if ($user)
				return $user;

			$user = $this->userDao->findByEmail($userNameOrEmail, $allowUnconfirmed);
			if ($user)
				return $user;

			throw new \InvalidArgumentException('User "' . $userNameOrEmail . '" was not found.');
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getByName($userName)
	{
		$transactionFunc = function() use ($userName)
		{
			$user = $this->userDao->findByName($userName);
			if (!$user)
				throw new \InvalidArgumentException('User with name "' . $userName . '" was not found.');
			return $user;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getById($userId)
	{
		$transactionFunc = function() use ($userId)
		{
			$user = $this->userDao->findById($userId);
			if (!$user)
				throw new \InvalidArgumentException('User with id "' . $userId . '" was not found.');
			return $user;
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function getFiltered(\Szurubooru\SearchServices\Filters\UserFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->userDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createUser(\Szurubooru\FormData\RegistrationFormData $formData)
	{
		$transactionFunc = function() use ($formData)
		{
			$formData->validate($this->validator);

			$user = new \Szurubooru\Entities\User();
			$user->setRegistrationTime($this->timeService->getCurrentTime());
			$user->setLastLoginTime(null);
			$user->setAccessRank($this->userDao->hasAnyUsers()
				? \Szurubooru\Entities\User::ACCESS_RANK_REGULAR_USER
				: \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR);

			$this->updateUserName($user, $formData->userName);
			$this->updateUserPassword($user, $formData->password);
			$this->updateUserAvatarStyle($user, \Szurubooru\Entities\User::AVATAR_STYLE_GRAVATAR);
			$this->updateUserEmail($user, $formData->email);
			return $this->userDao->save($user);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function updateUser(\Szurubooru\Entities\User $user, \Szurubooru\FormData\UserEditFormData $formData)
	{
		$transactionFunc = function() use ($user, $formData)
		{
			$this->validator->validate($formData);

			if ($formData->avatarStyle !== null)
				$this->updateUserAvatarStyle($user, $formData->avatarStyle);

			if ($formData->avatarContent !== null)
				$this->updateUserAvatarContent($user, $formData->avatarContent);

			if ($formData->userName !== null)
				$this->updateUserName($user, $formData->userName);

			if ($formData->password !== null)
				$this->updateUserPassword($user, $formData->password);

			if ($formData->email !== null)
				$this->updateUserEmail($user, $formData->email);

			if ($formData->accessRank !== null)
				$this->updateUserAccessRank($user, $formData->accessRank);

			if ($formData->browsingSettings !== null)
				$this->updateUserBrowsingSettings($user, $formData->browsingSettings);

			if ($formData->banned !== $user->isBanned())
				$user->setBanned(boolval($formData->banned));

			return $this->userDao->save($user);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function deleteUser(\Szurubooru\Entities\User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$this->userDao->deleteById($user->getId());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function sendPasswordResetEmail(\Szurubooru\Entities\User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$token = $this->tokenService->createAndSaveToken($user->getName(), \Szurubooru\Entities\Token::PURPOSE_PASSWORD_RESET);
			$this->emailService->sendPasswordResetEmail($user, $token);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function finishPasswordReset(\Szurubooru\Entities\Token $token)
	{
		$transactionFunc = function() use ($token)
		{
			if ($token->getPurpose() !== \Szurubooru\Entities\Token::PURPOSE_PASSWORD_RESET)
				throw new \Exception('This token is not a password reset token.');

			$user = $this->getByName($token->getAdditionalData());
			$newPassword = $this->passwordService->getRandomPassword();
			$user->setPasswordHash($this->passwordService->getHash($newPassword));
			$this->userDao->save($user);
			$this->tokenService->invalidateByName($token->getName());
			return $newPassword;
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function sendActivationEmail(\Szurubooru\Entities\User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$token = $this->tokenService->createAndSaveToken($user->getName(), \Szurubooru\Entities\Token::PURPOSE_ACTIVATE);
			$this->emailService->sendActivationEmail($user, $token);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function finishActivation(\Szurubooru\Entities\Token $token)
	{
		$transactionFunc = function() use ($token)
		{
			if ($token->getPurpose() !== \Szurubooru\Entities\Token::PURPOSE_ACTIVATE)
				throw new \Exception('This token is not an activation token.');

			$user = $this->getByName($token->getAdditionalData());
			$user = $this->confirmUserEmail($user);
			$this->userDao->save($user);
			$this->tokenService->invalidateByName($token->getName());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function updateUserAvatarStyle(\Szurubooru\Entities\User $user, $newAvatarStyle)
	{
		$user->setAvatarStyle($newAvatarStyle);
	}

	private function updateUserAvatarContent(\Szurubooru\Entities\User $user, $newAvatarContent)
	{
		$mime = \Szurubooru\Helpers\MimeHelper::getMimeTypeFromBuffer($newAvatarContent);
		if (!\Szurubooru\Helpers\MimeHelper::isImage($mime))
			throw new \DomainException('Avatar must be an image.');

		if (strlen($newAvatarContent) > $this->config->database->maxCustomThumbnailSize)
			throw new \DomainException('Upload is too big.');

		$user->setCustomAvatarSourceContent($newAvatarContent);
	}

	private function updateUserName(\Szurubooru\Entities\User $user, $newName)
	{
		$this->assertNoUserWithThisName($user, $newName);
		$user->setName($newName);
	}

	private function updateUserPassword(\Szurubooru\Entities\User $user, $newPassword)
	{
		$user->setPasswordHash($this->passwordService->getHash($newPassword));
	}

	private function updateUserEmail(\Szurubooru\Entities\User $user, $newEmail)
	{
		if ($user->getEmail() === $newEmail)
		{
			$user->setEmailUnconfirmed(null);
		}
		else
		{
			$this->assertNoUserWithThisEmail($user, $newEmail);
			$user->setEmailUnconfirmed($newEmail);
			$user = $this->sendActivationEmailIfNeeded($user);
		}
	}

	private function updateUserAccessRank(\Szurubooru\Entities\User $user, $newAccessRank)
	{
		$user->setAccessRank($newAccessRank);
	}

	private function updateUserBrowsingSettings(\Szurubooru\Entities\User $user, $newBrowsingSettings)
	{
		$user->setBrowsingSettings($newBrowsingSettings);
	}

	public function updateUserLastLoginTime(\Szurubooru\Entities\User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$user->setLastLoginTime($this->timeService->getCurrentTime());
			$this->userDao->save($user);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function sendActivationEmailIfNeeded(\Szurubooru\Entities\User $user)
	{
		if ($user->getAccessRank() === \Szurubooru\Entities\User::ACCESS_RANK_ADMINISTRATOR
			or !$this->config->security->needEmailActivationToRegister)
		{
			$user = $this->confirmUserEmail($user);
		}
		else
		{
			$this->sendActivationEmail($user);
		}
		return $user;
	}

	private function confirmUserEmail(\Szurubooru\Entities\User $user)
	{
		//security issue:
		//1. two users set their unconfirmed mail to godzilla@empire.gov
		//2. activation mail is sent to both of them
		//3. first user confirms, ok
		//4. second user confirms, ok
		//5. two users share the same mail --> problem.
		//by checking here again for users with such mail, this problem is solved with first-come first-serve approach:
		//whoever confirms e-mail first, wins.
		$this->assertNoUserWithThisEmail($user, $user->getEmailUnconfirmed());

		$user->setAccountConfirmed(true);
		$user->setEmail($user->getEmailUnconfirmed());
		$user->setEmailUnconfirmed(null);
		return $user;
	}

	private function assertNoUserWithThisName(\Szurubooru\Entities\User $owner, $nameToCheck)
	{
		$userWithThisName = $this->userDao->findByName($nameToCheck);
		if ($userWithThisName and $userWithThisName->getId() !== $owner->getId())
			throw new \DomainException('User with this name already exists.');
	}

	private function assertNoUserWithThisEmail(\Szurubooru\Entities\User $owner, $emailToCheck)
	{
		if (!$emailToCheck)
			return;
		$userWithThisEmail = $this->userDao->findByEmail($emailToCheck);
		if ($userWithThisEmail and $userWithThisEmail->getId() !== $owner->getId())
			throw new \DomainException('User with this e-mail already exists.');
	}
}
