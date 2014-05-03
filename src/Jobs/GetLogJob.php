<?php
class GetLogJob extends AbstractJob
{
	public function execute()
	{
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

		if (!empty($filter))
		{
			$lines = array_filter($lines, function($line) use ($filter)
			{
				return stripos($line, $filter) !== false;
			});
		}

		$lineCount = count($lines);
		$logsPerPage = intval(getConfig()->browsing->logsPerPage);
		$pageCount = ceil($lineCount / $logsPerPage);
		$page = min($pageCount, $page);

		$lines = array_slice($lines, ($page - 1) * $logsPerPage, $logsPerPage);

		$ret = new StdClass;
		$ret->lines = $lines;
		$ret->lineCount = $lineCount;
		$ret->page = $page;
		$ret->pageCount = $pageCount;
		return $ret;
	}

	public function requiresPrivilege()
	{
		return Privilege::ViewLog;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
