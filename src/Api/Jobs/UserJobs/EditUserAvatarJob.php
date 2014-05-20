<?php
class EditUserAvatarJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$state = $this->getArgument(JobArgs::ARG_NEW_AVATAR_STYLE);

		if ($state == UserAvatarStyle::Custom)
		{
			$file = $this->getArgument(JobArgs::ARG_NEW_AVATAR_CONTENT);
			$user->setCustomAvatarFromPath($file->filePath);
		}
		else
			$user->setAvatarStyle(new UserAvatarStyle($state));

		if ($this->getContext() == self::CONTEXT_NORMAL)
			UserModel::save($user);

		Logger::log('{user} changed avatar for {subject}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user)]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_AVATAR_STYLE,
			JobArgs::Optional(JobArgs::ARG_NEW_AVATAR_CONTENT));
	}

	public function getRequiredMainPrivilege()
	{
		return $this->getContext() == self::CONTEXT_BATCH_ADD
			? Privilege::RegisterAccount
			: Privilege::EditUserAvatar;
	}

	public function getRequiredSubPrivileges()
	{
		return Access::getIdentity($this->userRetriever->retrieve());
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}

