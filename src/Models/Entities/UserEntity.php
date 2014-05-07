<?php
use \Chibi\Sql as Sql;
use \Chibi\Database as Database;

class UserEntity extends AbstractEntity implements IValidatable
{
	protected $name;
	protected $passSalt;
	protected $passHash;
	public $staffConfirmed;
	protected $emailUnconfirmed;
	protected $emailConfirmed;
	public $joinDate;
	public $lastLoginDate;
	protected $accessRank;
	public $settings;
	protected $banned = false;

	protected $__passwordChanged = false;
	protected $__password;

	public function validate()
	{
		$this->validateUserName();
		$this->validatePassword();

		//todo: validate e-mails

		if (empty($this->getAccessRank()))
			throw new Exception('No access rank detected');

		if ($this->getAccessRank()->toInteger() == AccessRank::Anonymous)
			throw new Exception('Trying to save anonymous user into database');
	}


	protected function validateUserName()
	{
		$userName = $this->getName();
		$config = getConfig();

		$otherUser = UserModel::findByName($userName, false);
		if ($otherUser !== null and $otherUser->getId() != $this->getId())
		{
			if (!$otherUser->getConfirmedEmail()
				and isset($config->registration->needEmailForRegistering)
				and $config->registration->needEmailForRegistering)
			{
				throw new SimpleException('User with this name is already registered and awaits e-mail confirmation');
			}

			if (!$otherUser->staffConfirmed
				and $config->registration->staffActivation)
			{
				throw new SimpleException('User with this name is already registered and awaits staff confirmation');
			}

			throw new SimpleException('User with this name is already registered');
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

	public function validatePassword()
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

	public static function validateAccessRank(AccessRank $accessRank)
	{
		$accessRank->validate();

		if ($accessRank->toInteger() == AccessRank::Nobody)
			throw new Exception('Cannot set special access rank "%s"', $accessRank->toString());

		return $accessRank;
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
		$this->name = trim($name);
	}

	public function getUnconfirmedEmail()
	{
		return $this->emailUnconfirmed;
	}

	public function setUnconfirmedEmail($email)
	{
		$this->emailUnconfirmed = $email;
	}

	public function getConfirmedEmail()
	{
		return $this->emailConfirmed;
	}

	public function setConfirmedEmail($email)
	{
		$this->emailConfirmed = $email;
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
			return $safety->toInteger() == PostSafety::Safe;
		return $all & $safety->toFlag();
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

		if (!$new)
			$new = (new PostSafety(PostSafety::Safe))->toFlag();

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
		return Database::fetchOne($stmt)['count'];
	}

	public function getCommentCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('comment');
		$stmt->setCriterion(new Sql\EqualsFunctor('commenter_id', new Sql\Binding($this->getId())));
		return Database::fetchOne($stmt)['count'];
	}

	public function getPostCount()
	{
		$stmt = new Sql\SelectStatement();
		$stmt->setColumn(new Sql\AliasFunctor(new Sql\CountFunctor('1'), 'count'));
		$stmt->setTable('post');
		$stmt->setCriterion(new Sql\EqualsFunctor('uploader_id', new Sql\Binding($this->getId())));
		return Database::fetchOne($stmt)['count'];
	}
}
