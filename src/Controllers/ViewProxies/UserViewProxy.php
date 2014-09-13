<?php
namespace Szurubooru\Controllers\ViewProxies;

class UserViewProxy extends AbstractViewProxy
{
	private $privilegeService;

	public function __construct(\Szurubooru\Services\PrivilegeService $privilegeService)
	{
		$this->privilegeService = $privilegeService;
	}

	public function fromEntity($user)
	{
		$result = new \StdClass;
		if ($user)
		{
			$result->id = $user->getId();
			$result->name = $user->getName();
			$result->accessRank = \Szurubooru\Helpers\EnumHelper::accessRankToString($user->getAccessRank());
			$result->registrationTime = $user->getRegistrationTime();
			$result->lastLoginTime = $user->getLastLoginTime();
			$result->avatarStyle = $user->getAvatarStyle();

			if ($this->privilegeService->isLoggedIn($user))
			{
				$result->browsingSettings = $user->getBrowsingSettings();
			}

			if ($this->privilegeService->hasPrivilege(\Szurubooru\Privilege::VIEW_ALL_EMAIL_ADDRESSES) or
				$this->privilegeService->isLoggedIn($user))
			{
				$result->email = $user->getEmail();
				$result->emailUnconfirmed = $user->getEmailUnconfirmed();
			}

			$result->confirmed = !$user->getEmailUnconfirmed();
		}
		return $result;
	}
}
