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
					$this->privilegeMap[$allowedAccessRank][] = $privilegeName;
				}
			}
		}
	}

	public function getCurrentPrivileges()
	{
		$currentAccessRank = $this->authService->getLoggedInUser()->getAccessRank();
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
			$this->fail();
	}

	public function assertLoggedIn($userIdentifier = null)
	{
		if ($userIdentifier)
		{
			if (!$this->isLoggedIn($userIdentifier))
				$this->fail();
		}
		else
		{
			if (!$this->authService->getLoggedInUser())
				$this->fail();
		}
	}

	public function isLoggedIn($userIdentifier)
	{
		$loggedInUser = $this->authService->getLoggedInUser();
		if ($userIdentifier instanceof \Szurubooru\Entities\User)
		{
			return $loggedInUser->getId() and ($loggedInUser->getId() === $userIdentifier->getId());
		}
		elseif (is_string($userIdentifier))
		{
			if ($loggedInUser->getEmail())
			{
				if ($loggedInUser->getEmail() === $userIdentifier)
					return true;
			}
			return $loggedInUser->getName() === $userIdentifier;
		}
		else
		{
			throw new \InvalidArgumentException('Invalid user identifier.');
		}
	}

	private function fail()
	{
		throw new \DomainException('Unprivileged operation');
	}
}
