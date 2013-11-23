<?php
require_once __DIR__ . '/../src/core.php';

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
	echo 'ID: ' . $user->id . PHP_EOL;
	echo 'Name: ' . $user->name . PHP_EOL;
	echo 'E-mail: ' . $user->email_unconfirmed . PHP_EOL;
	echo 'Date joined: ' . date('Y-m-d H:i:s', $user->join_date) . PHP_EOL;
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
			Model_User::remove($user);
		};
		break;

	default:
		die('Unknown action' . PHP_EOL);
}

$rows = R::find('user', 'email_confirmed IS NULL AND DATETIME(join_date) < DATETIME("now", "-21 days")');
foreach ($rows as $user)
{
	$func($user);
}
