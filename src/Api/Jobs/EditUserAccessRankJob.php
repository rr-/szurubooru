<?php
class EditUserAccessRankJob extends AbstractUserJob
{
	const NEW_ACCESS_RANK = 'new-access-rank';

	public function execute()
	{
		$user = $this->user;
		$newAccessRank = UserModel::validateAccessRank($this->getArgument(self::NEW_ACCESS_RANK));

		$oldAccessRank = $user->accessRank;
		if ($oldAccessRank == $newAccessRank)
			return $user;

		$user->accessRank = $newAccessRank;

		UserModel::save($user);

		LogHelper::log('{user} changed {subject}\'s access rank to {rank}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'subject' => TextHelper::reprUser($user),
			'rank' => AccessRank::toString($newAccessRank)]);

		return $user;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::ChangeUserEmail,
			Access::getIdentity($this->user),
		];
	}
}
