<?php
class GetLogJob extends AbstractPageJob
{
	public function execute()
	{
		$pageSize = $this->getPageSize();
		$page = $this->getArgument(self::PAGE_NUMBER);
		$name = $this->getArgument(self::LOG_ID);
		$query = $this->getArgument(self::QUERY);

		//parse input
		$page = max(1, intval($page));
		$name = str_replace(['/', '\\'], '', $name); //paranoia mode
		$path = TextHelper::absolutePath(getConfig()->main->logsPath . DS . $name);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Specified log doesn\'t exist');

		//load lines
		$lines = file_get_contents($path);
		$lines = explode(PHP_EOL, str_replace(["\r", "\n"], PHP_EOL, $lines));
		$lines = array_reverse($lines);

		if (!empty($query))
		{
			$lines = array_filter($lines, function($line) use ($query)
			{
				return stripos($line, $query) !== false;
			});
		}

		$lineCount = count($lines);
		$lines = array_slice($lines, ($page - 1) * $pageSize, $pageSize);

		return $this->getPager($lines, $lineCount, $page, $pageSize);
	}

	public function getDefaultPageSize()
	{
		return intval(getConfig()->browsing->logsPerPage);
	}

	public function requiresPrivilege()
	{
		return Privilege::ViewLog;
	}
}
