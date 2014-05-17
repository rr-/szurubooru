<?php
class EditUserAccessRankJob extends AbstractJob
{
	protected $userRetriever;

	public function __construct()
	{
		$this->userRetriever = new UserRetriever($this);
	}

	public function execute()
	{
		$user = $this->userRetriever->retrieve();
		$newAccessRank = new AccessRank($this->getArgument(JobArgs::ARG_NEW_ACCESS_RANK));

		$oldAccessRank = $user->getAccessRank();
		if ($oldAccessRank == $newAccessRank)
			return $user;

		$user->setAccessRank($newAccessRank);

		if ($this->getContext() == self::CONTEXT_NORMAL)
			UserModel::save($user);

		Logger::log('{user} changed {subject}\'s access rank to {rank}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user),
			'rank' => $newAccessRank->toString()]);

		return $user;
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->userRetriever->getRequiredArguments(),
			JobArgs::ARG_NEW_ACCESS_RANK);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::EditUserAccessRank;
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
