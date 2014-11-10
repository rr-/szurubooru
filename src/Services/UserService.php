<?php
namespace Szurubooru\Services;
use Szurubooru\Config;
use Szurubooru\Dao\TransactionManager;
use Szurubooru\Dao\UserDao;
use Szurubooru\Entities\Token;
use Szurubooru\Entities\User;
use Szurubooru\FormData\RegistrationFormData;
use Szurubooru\FormData\UserEditFormData;
use Szurubooru\Helpers\MimeHelper;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\SearchServices\Filters\UserFilter;
use Szurubooru\Services\EmailService;
use Szurubooru\Services\PasswordService;
use Szurubooru\Services\TimeService;
use Szurubooru\Services\TokenService;
use Szurubooru\Validator;

class UserService
{
	private $config;
	private $validator;
	private $transactionManager;
	private $userDao;
	private $passwordService;
	private $emailService;
	private $timeService;
	private $tokenService;

	public function __construct(
		Config $config,
		Validator $validator,
		TransactionManager $transactionManager,
		UserDao $userDao,
		PasswordService $passwordService,
		EmailService $emailService,
		TimeService $timeService,
		TokenService $tokenService)
	{
		$this->config = $config;
		$this->validator = $validator;
		$this->transactionManager = $transactionManager;
		$this->userDao = $userDao;
		$this->passwordService = $passwordService;
		$this->emailService = $emailService;
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

	public function getFiltered(UserFilter $filter)
	{
		$transactionFunc = function() use ($filter)
		{
			return $this->userDao->findFiltered($filter);
		};
		return $this->transactionManager->rollback($transactionFunc);
	}

	public function createUser(RegistrationFormData $formData)
	{
		$transactionFunc = function() use ($formData)
		{
			$formData->validate($this->validator);

			$user = new User();
			$user->setRegistrationTime($this->timeService->getCurrentTime());
			$user->setLastLoginTime(null);
			$user->setAccessRank($this->userDao->hasAnyUsers()
				? $this->getDefaultAccessRank()
				: User::ACCESS_RANK_ADMINISTRATOR);
			$user->setPasswordSalt($this->passwordService->getRandomPassword());

			$this->updateUserName($user, $formData->userName);
			$this->updateUserPassword($user, $formData->password);
			$this->updateUserAvatarStyle($user, User::AVATAR_STYLE_GRAVATAR);
			$this->updateUserEmail($user, $formData->email);
			return $this->userDao->save($user);
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function updateUser(User $user, UserEditFormData $formData)
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

	public function deleteUser(User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$this->userDao->deleteById($user->getId());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function sendPasswordResetEmail(User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$token = $this->tokenService->createAndSaveToken($user->getName(), Token::PURPOSE_PASSWORD_RESET);
			$this->emailService->sendPasswordResetEmail($user, $token);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function finishPasswordReset(Token $token)
	{
		$transactionFunc = function() use ($token)
		{
			if ($token->getPurpose() !== Token::PURPOSE_PASSWORD_RESET)
				throw new \Exception('This token is not a password reset token.');

			$user = $this->getByName($token->getAdditionalData());
			$newPassword = $this->passwordService->getRandomPassword();
			$this->updateUserPassword($user, $newPassword);
			$this->userDao->save($user);
			$this->tokenService->invalidateByName($token->getName());
			return $newPassword;
		};
		return $this->transactionManager->commit($transactionFunc);
	}

	public function sendActivationEmail(User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$token = $this->tokenService->createAndSaveToken($user->getName(), Token::PURPOSE_ACTIVATE);
			$this->emailService->sendActivationEmail($user, $token);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	public function finishActivation(Token $token)
	{
		$transactionFunc = function() use ($token)
		{
			if ($token->getPurpose() !== Token::PURPOSE_ACTIVATE)
				throw new \Exception('This token is not an activation token.');

			$user = $this->getByName($token->getAdditionalData());
			$user = $this->confirmUserEmail($user);
			$this->userDao->save($user);
			$this->tokenService->invalidateByName($token->getName());
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function updateUserAvatarStyle(User $user, $newAvatarStyle)
	{
		$user->setAvatarStyle($newAvatarStyle);
	}

	private function updateUserAvatarContent(User $user, $newAvatarContent)
	{
		$mime = MimeHelper::getMimeTypeFromBuffer($newAvatarContent);
		if (!MimeHelper::isImage($mime))
			throw new \DomainException('Avatar must be an image.');

		if (strlen($newAvatarContent) > $this->config->database->maxCustomThumbnailSize)
			throw new \DomainException('Upload is too big.');

		$user->setCustomAvatarSourceContent($newAvatarContent);
	}

	private function updateUserName(User $user, $newName)
	{
		$this->assertNoUserWithThisName($user, $newName);
		$user->setName($newName);
	}

	private function updateUserPassword(User $user, $newPassword)
	{
		$user->setPasswordHash($this->passwordService->getHash($newPassword, $user->getPasswordSalt()));
	}

	private function updateUserEmail(User $user, $newEmail)
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

	private function updateUserAccessRank(User $user, $newAccessRank)
	{
		$user->setAccessRank($newAccessRank);
	}

	private function updateUserBrowsingSettings(User $user, $newBrowsingSettings)
	{
		$user->setBrowsingSettings($newBrowsingSettings);
	}

	public function updateUserLastLoginTime(User $user)
	{
		$transactionFunc = function() use ($user)
		{
			$user->setLastLoginTime($this->timeService->getCurrentTime());
			$this->userDao->save($user);
		};
		$this->transactionManager->commit($transactionFunc);
	}

	private function sendActivationEmailIfNeeded(User $user)
	{
		if ($user->getAccessRank() === User::ACCESS_RANK_ADMINISTRATOR
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

	private function confirmUserEmail(User $user)
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

	private function assertNoUserWithThisName(User $owner, $nameToCheck)
	{
		$userWithThisName = $this->userDao->findByName($nameToCheck);
		if ($userWithThisName && $userWithThisName->getId() !== $owner->getId())
			throw new \DomainException('User with this name already exists.');
	}

	private function assertNoUserWithThisEmail(User $owner, $emailToCheck)
	{
		if (!$emailToCheck)
			return;
		$userWithThisEmail = $this->userDao->findByEmail($emailToCheck);
		if ($userWithThisEmail && $userWithThisEmail->getId() !== $owner->getId())
			throw new \DomainException('User with this e-mail already exists.');
	}

	private function getDefaultAccessRank()
	{
		return EnumHelper::accessRankFromString($this->config->security->defaultAccessRank);
	}
}
