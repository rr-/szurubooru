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
			$result->id = $user->id;
			$result->name = $user->name;
			$result->accessRank = \Szurubooru\Helpers\EnumHelper::accessRankToString($user->accessRank);
			$result->registrationTime = $user->registrationTime;
			$result->lastLoginTime = $user->lastLoginTime;
			$result->avatarStyle = $user->avatarStyle;

			if ($this->privilegeService->isLoggedIn($user))
			{
				$result->browsingSettings = $user->browsingSettings;
			}

			if ($this->privilegeService->hasPrivilege(\Szurubooru\Privilege::VIEW_ALL_EMAIL_ADDRESSES) or
				$this->privilegeService->isLoggedIn($user))
			{
				$result->email = $user->email;
				$result->emailUnconfirmed = $user->emailUnconfirmed;
			}
		}
		return $result;
	}
}
