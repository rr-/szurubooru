<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

final class UserEntity extends AbstractEntity implements IValidatable
{
	private $name;
	private $passSalt;
	private $passHash;
	private $staffConfirmed;
	private $emailUnconfirmed;
	private $emailConfirmed;
	private $joinDate;
	private $lastLoginDate;
	private $accessRank;
	public $settings;
	private $banned = false;

	private $__passwordChanged = false;
	private $__password;

	public function fillNew()
	{
		$this->setAccessRank(new AccessRank(AccessRank::Anonymous));
		$this->setPasswordSalt(md5(mt_rand() . uniqid()));
	}

	public function fillFromDatabase($row)
	{
		$this->id = (int) $row['id'];
		$this->name = $row['name'];
		$this->passSalt = $row['pass_salt'];
		$this->passHash = $row['pass_hash'];
		$this->staffConfirmed = $row['staff_confirmed'];
		$this->emailUnconfirmed = $row['email_unconfirmed'];
		$this->emailConfirmed = $row['email_confirmed'];
		$this->joinDate = $row['join_date'];
		$this->lastLoginDate = $row['last_login_date'];
		$this->settings = $row['settings'];
		$this->banned = $row['banned'];
		$this->setAccessRank(new AccessRank($row['access_rank']));
	}

	public function validate()
	{
		$this->validateUserName();
		$this->validatePassword();
		$this->validateAccessRank();
		$this->validateEmails();

		if (!$this->getSetting(UserModel::SETTING_SAFETY))
			$this->setSetting(UserModel::SETTING_SAFETY, (new PostSafety(PostSafety::Safe))->toFlag());

		if (empty($this->getAccessRank()))
			throw new Exception('No access rank detected');

		if ($this->getAccessRank()->toInteger() == AccessRank::Anonymous)
			throw new Exception('Trying to save anonymous user into database');
	}

	private function validateUserName()
	{
		$userName = $this->getName();
		$config = getConfig();

		$otherUser = UserModel::tryGetByName($userName);
		if ($otherUser !== null and $otherUser->getId() != $this->getId())
		{
			$this->throwDuplicateUserError($otherUser, 'name');
		}

		$userNameMinLength = intval($config->registration->userNameMinLength);
		$userNameMaxLength = intval($config->registration->userNameMaxLength);
		$userNameRegex = $config->registration->userNameRegex;

		if (strlen($userName) < $userNameMinLength)
			throw new SimpleException('User name must have at least %d characters', $userNameMinLength);

		if (strlen($userName) > $userNameMaxLength)
			throw new SimpleException('User name must have at most %d characters', $userNameMaxLength);

		if (!preg_match($userNameRegex, $userName))
			throw new SimpleException('User name contains invalid characters');
	}

	private function validatePassword()
	{
		if (empty($this->getPasswordHash()))
			throw new Exception('Trying to save user with no password into database');

		if (!$this->__passwordChanged)
			return;

		$config = getConfig();
		$passMinLength = intval($config->registration->passMinLength);
		$passRegex = $config->registration->passRegex;

		$password = $this->__password;

		if (strlen($password) < $passMinLength)
			throw new SimpleException('Password must have at least %d characters', $passMinLength);

		if (!preg_match($passRegex, $password))
			throw new SimpleException('Password contains invalid characters');
	}

	private function validateAccessRank()
	{
		$this->accessRank->validate();

		if ($this->accessRank->toInteger() == AccessRank::Nobody)
			throw new Exception(sprintf('Cannot set special access rank "%s"', $this->accessRank->toString()));
	}

	private function validateEmails()
	{
		$this->validateEmail($this->getUnconfirmedEmail());
		$this->validateEmail($this->getConfirmedEmail());
	}

	private function validateEmail($email)
	{
		if (!empty($email) and !TextHelper::isValidEmail($email))
			throw new SimpleException('E-mail address appears to be invalid');

		$otherUser = UserModel::tryGetByEmail($email);
		if ($otherUser !== null and $otherUser->getId() != $this->getId())
		{
			$this->throwDuplicateUserError($otherUser, 'e-mail');
		}
	}

	private function throwDuplicateUserError($otherUser, $reason)
	{
		$config = getConfig();

		if (!$otherUser->getConfirmedEmail()
			and isset($config->registration->needEmailForRegistering)
			and $config->registration->needEmailForRegistering)
		{
			throw new SimpleException(
				'User with this %s is already registered and awaits e-mail confirmation',
				$reason);
		}

		if (!$otherUser->isStaffConfirmed()
			and $config->registration->staffActivation)
		{
			throw new SimpleException(
				'User with this %s is already registered and awaits staff confirmation',
				$reason);
		}

		throw new SimpleException(
			'User with this %s is already registered',
			$reason);
	}

	public function isBanned()
	{
		return $this->banned;
	}

	public function ban()
	{
		$this->banned = true;
	}

	public function unban()
	{
		$this->banned = false;
	}

	public function getName()
	{
		return $this->name;
	}

	public function setName($name)
	{
		$this->name = $name === null ? null : trim($name);
	}

	public function getJoinTime()
	{
		return $this->joinDate;
	}

	public function setJoinTime($unixTime)
	{
		$this->joinDate = $unixTime;
	}

	public function getLastLoginTime()
	{
		return $this->lastLoginDate;
	}

	public function setLastLoginTime($unixTime)
	{
		$this->lastLoginDate = $unixTime;
	}

	public function getUnconfirmedEmail()
	{
		return $this->emailUnconfirmed;
	}

	public function setUnconfirmedEmail($email)
	{
		$this->emailUnconfirmed = $email === null ? null : trim($email);
	}

	public function getConfirmedEmail()
	{
		return $this->emailConfirmed;
	}

	public function setConfirmedEmail($email)
	{
		$this->emailConfirmed = $email === null ? null : trim($email);
	}

	public function isStaffConfirmed()
	{
		return $this->staffConfirmed;
	}

	public function setStaffConfirmed($confirmed)
	{
		$this->staffConfirmed = $confirmed;
	}

	public function getPasswordHash()
	{
		return $this->passHash;
	}

	public function getPasswordSalt()
	{
		return $this->passSalt;
	}

	public function setPasswordSalt($passSalt)
	{
		$this->passSalt = $passSalt;
		$this->passHash = null;
	}

	public function setPassword($password)
	{
		$this->__passwordChanged = true;
		$this->__password = $password;
		$this->passHash = UserModel::hashPassword($password, $this->passSalt);
	}

	public function getAccessRank()
	{
		return $this->accessRank;
	}

	public function setAccessRank(AccessRank $accessRank)
	{
		$accessRank->validate();
		$this->accessRank = $accessRank;
	}

	public function getAvatarUrl($size = 32)
	{
		$subject = !empty($this->getConfirmedEmail())
			? $this->getConfirmedEmail()
			: $this->passSalt . $this->getName();
		$hash = md5(strtolower(trim($subject)));
		$url = 'http://www.gravatar.com/avatar/' . $hash . '?s=' . $size . '&d=retro';
		return $url;
	}

	public function getSetting($key)
	{
		$settings = json_decode($this->settings, true);
		return isset($settings[$key])
			? $settings[$key]
			: null;
	}

	public function setSetting($key, $value)
	{
		$settings = json_decode($this->settings, true);
		$settings[$key] = $value;
		$settings = json_encode($settings);
		if (strlen($settings) > 200)
			throw new SimpleException('Too much data');
		$this->settings = $settings;
	}

	public function hasEnabledSafety(PostSafety $safety)
	{
		$all = $this->getSetting(UserModel::SETTING_SAFETY);
		if (!$all)
			return $safety->toInteger() == (new PostSafety(PostSafety::Safe))->toInteger();
		return ($all & $safety->toFlag()) == $safety->toFlag();
	}

	public function enableSafety(PostSafety $safety, $enabled)
	{
		$all = $this->getSetting(UserModel::SETTING_SAFETY);

		$new = $all;
		if (!$enabled)
		{
			$new &= ~$safety->toFlag();
		}
		else
		{
			$new |= $safety->toFlag();
		}

		$this->setSetting(UserModel::SETTING_SAFETY, $new);
	}

	public function hasEnabledHidingDislikedPosts()
	{
		$ret = $this->getSetting(UserModel::SETTING_HIDE_DISLIKED_POSTS);
		if ($ret === null)
			$ret = !getConfig()->browsing->showDislikedPostsDefault;
		return $ret;
	}

	public function enableHidingDislikedPosts($enabled)
	{
		$this->setSetting(UserModel::SETTING_HIDE_DISLIKED_POSTS, $enabled ? 1 : 0);
	}

	public function hasEnabledPostTagTitles()
	{
		$ret = $this->getSetting(UserModel::SETTING_POST_TAG_TITLES);
		if ($ret === null)
			$ret = getConfig()->browsing->showPostTagTitlesDefault;
		return $ret;
	}

	public function enablePostTagTitles($enabled)
	{
		$this->setSetting(UserModel::SETTING_POST_TAG_TITLES, $enabled ? 1 : 0);
	}

	public function hasEnabledEndlessScrolling()
	{
		$ret = $this->getSetting(UserModel::SETTING_ENDLESS_SCROLLING);
		if ($ret === null)
			$ret = getConfig()->browsing->endlessScrollingDefault;
		return $ret;
	}

	public function enableEndlessScrolling($enabled)
	{
		$this->setSetting(UserModel::SETTING_ENDLESS_SCROLLING, $enabled ? 1 : 0);
	}

	public function confirmEmail()
	{
		if (empty($this->getUnconfirmedEmail()))
			return;

		$this->setConfirmedEmail($this->getUnconfirmedEmail());
		$this->setUnconfirmedEmail(null);
	}

	public function hasFavorited($post)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('favoritee');
		$stmt->setCriterion((new Sql\ConjunctionFunctor)
			->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->getId())))
			->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->getId()))));
		return Database::fetchOne($stmt)['count'] == 1;
	}

	public function getScore($post)
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn('score');
		$stmt->setTable('post_score');
		$stmt->setCriterion((new Sql\ConjunctionFunctor)
			->add(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->getId())))
			->add(new Sql\EqualsFunctor('post_id', new Sql\Binding($post->getId()))));
		$row = Database::fetchOne($stmt);
		if ($row)
			return intval($row['score']);
		return null;
	}

	public function getFavoriteCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('favoritee');
		$stmt->setCriterion(new Sql\EqualsFunctor('user_id', new Sql\Binding($this->getId())));
		return (int) Database::fetchOne($stmt)['count'];
	}

	public function getCommentCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('comment');
		$stmt->setCriterion(new Sql\EqualsFunctor('commenter_id', new Sql\Binding($this->getId())));
		return (int) Database::fetchOne($stmt)['count'];
	}

	public function getPostCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('post');
		$stmt->setCriterion(new Sql\EqualsFunctor('uploader_id', new Sql\Binding($this->getId())));
		return (int) Database::fetchOne($stmt)['count'];
	}
}
