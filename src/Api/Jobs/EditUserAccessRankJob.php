<?php
class EditUserAccessRankJob extends AbstractUserEditJob
{
	const NEW_ACCESS_RANK = 'new-access-rank';

	public function execute()
	{
		$user = $this->user;
		$newAccessRank = new AccessRank($this->getArgument(self::NEW_ACCESS_RANK));

		$oldAccessRank = $user->getAccessRank();
		if ($oldAccessRank == $newAccessRank)
			return $user;

		$user->setAccessRank($newAccessRank);

		if (!$this->skipSaving)
			UserModel::save($user);

		LogHelper::log('{user} changed {subject}\'s access rank to {rank}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user),
			'rank' => $newAccessRank->toString()]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::ChangeUserEmail,
			Access::getIdentity($this->user));
	}
}
