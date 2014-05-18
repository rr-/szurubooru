<?php
class ListLogsJob extends AbstractJob
{
	public function execute()
	{
		$path = TextHelper::absolutePath(Core::getConfig()->main->logsPath);

		$logs = [];
		foreach (glob(dirname($path) . DS . '*.log') as $log)
			$logs []= basename($log);

		usort($logs, function($a, $b)
		{
			return strnatcasecmp($b, $a); //reverse natcasesort
		});

		return $logs;
	}

	public function getRequiredArguments()
	{
		return null;
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::ListLogs;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
