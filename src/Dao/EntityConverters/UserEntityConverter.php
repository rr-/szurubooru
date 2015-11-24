<?php
namespace Szurubooru\Dao\EntityConverters;
use Szurubooru\Entities\Entity;
use Szurubooru\Entities\User;

class UserEntityConverter extends AbstractEntityConverter implements IEntityConverter
{
    public function toBasicArray(Entity $entity)
    {
        return
        [
            'name' => $entity->getName(),
            'email' => $entity->getEmail(),
            'emailUnconfirmed' => $entity->getEmailUnconfirmed(),
            'passwordHash' => $entity->getPasswordHash(),
            'passwordSalt' => $entity->getPasswordSalt(),
            'accessRank' => $entity->getAccessRank(),
            'creationTime' => $this->entityTimeToDbTime($entity->getCreationTime()),
            'lastLoginTime' => $this->entityTimeToDbTime($entity->getLastLoginTime()),
            'avatarStyle' => $entity->getAvatarStyle(),
            'browsingSettings' => json_encode($entity->getBrowsingSettings()),
            'accountConfirmed' => intval($entity->isAccountConfirmed()),
            'banned' => intval($entity->isBanned()),
        ];
    }

    public function toBasicEntity(array $array)
    {
        $entity = new User(intval($array['id']));
        $entity->setName($array['name']);
        $entity->setEmail($array['email']);
        $entity->setEmailUnconfirmed($array['emailUnconfirmed']);
        $entity->setPasswordHash($array['passwordHash']);
        $entity->setPasswordSalt($array['passwordSalt']);
        $entity->setAccessRank(intval($array['accessRank']));
        $entity->setCreationTime($this->dbTimeToEntityTime($array['creationTime']));
        $entity->setLastLoginTime($this->dbTimeToEntityTime($array['lastLoginTime']));
        $entity->setAvatarStyle(intval($array['avatarStyle']));
        $entity->setBrowsingSettings(json_decode($array['browsingSettings']));
        $entity->setAccountConfirmed($array['accountConfirmed']);
        $entity->setBanned($array['banned']);
        return $entity;
    }
}
