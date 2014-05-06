<?php
class EditUserAccessRankJob extends AbstractUserJob
{
	const NEW_ACCESS_RANK = 'new-access-rank';

	public function isSatisfied()
	{
		return $this->hasArgument(self::NEW_ACCESS_RANK);
	}

	public function execute()
	{
		$user = $this->user;
		$newAccessRank = new AccessRank($this->getArgument(self::NEW_ACCESS_RANK));

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

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ChangeUserAccessRank,
			Access::getIdentity($this->user));
	}
}
