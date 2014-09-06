<?php
namespace Szurubooru\Services;

class PrivilegeService
{
	private $authService;
	private $privilegeMap;

	public function __construct(
		\Szurubooru\Config $config,
		\Szurubooru\Services\AuthService $authService)
	{
		$this->authService = $authService;

		if (isset($config->security->privileges))
		{
			foreach ($config->security->privileges as $privilegeName => $allowedAccessRanks)
			{
				$allowedAccessRanks = array_filter(preg_split('/[;,\s]+/', $allowedAccessRanks));
				foreach ($allowedAccessRanks as $allowedAccessRank)
				{
					if (!isset($this->privilegeMap[$allowedAccessRank]))
						$this->privilegeMap[$allowedAccessRank] = [];
					$this->privilegeMap[$allowedAccessRank] []= $privilegeName;
				}
			}
		}
	}

	public function getCurrentPrivileges()
	{
		$currentAccessRank = $this->authService->getLoggedInUser()->accessRank;
		$currentAccessRankName = \Szurubooru\Helpers\EnumHelper::accessRankToString($currentAccessRank);
		if (!isset($this->privilegeMap[$currentAccessRankName]))
			return [];
		return $this->privilegeMap[$currentAccessRankName];
	}

	public function hasPrivilege($privilege)
	{
		return in_array($privilege, $this->getCurrentPrivileges());
	}

	public function assertPrivilege($privilege)
	{
		if (!$this->hasPrivilege($privilege))
			throw new \DomainException('Unprivileged operation');
	}

	public function isLoggedIn($userIdentifier)
	{
		$loggedInUser = $this->authService->getLoggedInUser();
		if ($userIdentifier instanceof \Szurubooru\Entities\User)
			return $loggedInUser->name == $userIdentifier->name;
		elseif (is_string($userIdentifier))
			return $loggedInUser->name == $userIdentifier;
		else
			throw new \InvalidArgumentException('Invalid user identifier.');
	}
}
