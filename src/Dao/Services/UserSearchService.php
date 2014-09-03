<?php
namespace Szurubooru\Dao\Services;

class UserSearchService extends AbstractSearchService
{
	public function __construct(\Szurubooru\Dao\UserDao $userDao)
	{
		parent::__construct($userDao);
	}

	protected function getOrderColumn($token)
	{
		switch ($token)
		{
			case 'name':
				return 'name';

			case 'registrationDate':
			case 'registrationTime':
			case 'registered':
			case 'joinDate':
			case 'joinTime':
			case 'joined':
				return 'registrationTime';

			default:
				return null;
		}
	}

}
