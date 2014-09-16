<?php
namespace Szurubooru\Dao\EntityConverters;

class UserEntityConverter implements IEntityConverter
{
	public function toArray(\Szurubooru\Entities\Entity $entity)
	{
		return
		[
			'id' => $entity->getId(),
			'name' => $entity->getName(),
			'email' => $entity->getEmail(),
			'emailUnconfirmed' => $entity->getEmailUnconfirmed(),
			'passwordHash' => $entity->getPasswordHash(),
			'accessRank' => $entity->getAccessRank(),
			'registrationTime' => $entity->getRegistrationTime(),
			'lastLoginTime' => $entity->getLastLoginTime(),
			'avatarStyle' => $entity->getAvatarStyle(),
			'browsingSettings' => $entity->getBrowsingSettings(),
			'accountConfirmed' => $entity->isAccountConfirmed(),
		];
	}

	public function toEntity(array $array)
	{
		$entity = new \Szurubooru\Entities\User(intval($array['id']));
		$entity->setName($array['name']);
		$entity->setEmail($array['email']);
		$entity->setEmailUnconfirmed($array['emailUnconfirmed']);
		$entity->setPasswordHash($array['passwordHash']);
		$entity->setAccessRank(intval($array['accessRank']));
		$entity->setRegistrationTime($array['registrationTime']);
		$entity->setLastLoginTime($array['lastLoginTime']);
		$entity->setAvatarStyle(intval($array['avatarStyle']));
		$entity->setBrowsingSettings($array['browsingSettings']);
		$entity->setAccountConfirmed($array['accountConfirmed']);
		return $entity;
	}
}
