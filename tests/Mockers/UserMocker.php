<?php
class UserMocker extends AbstractMocker implements IMocker
{
	public function mockSingle()
	{
		$user = UserModel::spawn();
		$user->setAccessRank(new AccessRank(AccessRank::Registered));
		$user->setName('dummy'.uniqid());
		$user->setPassword('sekai');
		return UserModel::save($user);
	}
}
