<?php
namespace Szurubooru\ViewProxies;
use Szurubooru\Helpers\EnumHelper;
use Szurubooru\Privilege;
use Szurubooru\Services\PrivilegeService;

class UserViewProxy extends AbstractViewProxy
{
    private $privilegeService;

    public function __construct(PrivilegeService $privilegeService)
    {
        $this->privilegeService = $privilegeService;
    }

    public function fromEntity($user, $config = [])
    {
        $result = new \StdClass;
        if ($user)
        {
            $result->id = $user->getId();
            $result->name = $user->getName();
            $result->registrationTime = $user->getRegistrationTime();
            $result->lastLoginTime = $user->getLastLoginTime();
            $result->avatarStyle = EnumHelper::avatarStyleToString($user->getAvatarStyle());
            $result->banned = $user->isBanned();

            if ($this->privilegeService->isLoggedIn($user))
            {
                $result->browsingSettings = $user->getBrowsingSettings();
            }

            if ($this->privilegeService->hasPrivilege(Privilege::VIEW_ALL_ACCESS_RANKS) or
                $this->privilegeService->isLoggedin($user))
            {
                $result->accessRank = EnumHelper::accessRankToString($user->getAccessRank());
            }

            if ($this->privilegeService->hasPrivilege(Privilege::VIEW_ALL_EMAIL_ADDRESSES) or
                $this->privilegeService->isLoggedIn($user))
            {
                $result->email = $user->getEmail();
                $result->emailUnconfirmed = $user->getEmailUnconfirmed();
            }

            $result->confirmed = $user->isAccountConfirmed();
        }
        return $result;
    }
}
