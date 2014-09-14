<?php
namespace Szurubooru\Dao\Services;

class UserSearchService extends AbstractSearchService
{
	public function __construct(
		\Szurubooru\DatabaseConnection $databaseConnection,
		\Szurubooru\Dao\UserDao $userDao)
	{
		parent::__construct($databaseConnection, $userDao);
	}

	protected function getOrderColumn($token)
	{
		if ($token === 'name')
			return 'name';

		if (in_array($token, ['registrationDate', 'registrationTime', 'registered', 'joinDate', 'joinTime', 'joined']))
			return 'registrationTime';

		return null;
	}
}
