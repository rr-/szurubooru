<?php
require_once __DIR__ . '/../src/core.php';

Access::disablePrivilegeChecking();

function usage()
{
	echo 'Usage: ' . basename(__FILE__);
	echo ' -print|-purge';
	return true;
}

array_shift($argv);
if (empty($argv))
	usage() and die;

function printUser($user)
{
	echo 'ID: ' . $user->getId() . PHP_EOL;
	echo 'Name: ' . $user->getName() . PHP_EOL;
	echo 'E-mail: ' . $user->getUnconfirmedEmail() . PHP_EOL;
	echo 'Date joined: ' . date('Y-m-d H:i:s', $user->getJoinTime()) . PHP_EOL;
	echo PHP_EOL;
}

$action = array_shift($argv);
switch ($action)
{
	case '-print':
		$func = 'printUser';
		break;

	case '-purge':
		$func = function($user)
		{
			printUser($user);
			UserModel::remove($user);
		};
		break;

	default:
		die('Unknown action' . PHP_EOL);
}

$users = UserSearchService::getEntities(null, null, null);
foreach ($users as $user)
{
	if (!$user->getConfirmedEmail()
		and !$user->getLastLoginTime()
		and ((time() - $user->getJoinTime()) > 21 * 24 * 60 * 60))
	{
		$func($user);
	}
}
