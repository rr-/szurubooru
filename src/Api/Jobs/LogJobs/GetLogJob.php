<?php
class GetLogJob extends AbstractJob implements IPagedJob
{
	protected $pager;

	public function __construct()
	{
		$this->pager = new JobPager($this);
		$this->pager->setPageSize(getConfig()->browsing->logsPerPage);
	}

	public function getPager()
	{
		return $this->pager;
	}

	public function execute()
	{
		$pageSize = $this->pager->getPageSize();
		$page = $this->pager->getPageNumber();
		$name = $this->getArgument(JobArgs::ARG_LOG_ID);
		$query = $this->getArgument(JobArgs::ARG_QUERY);

		//parse input
		$page = max(1, intval($page));
		$name = str_replace(['/', '\\'], '', $name); //paranoia mode
		$path = TextHelper::absolutePath(dirname(getConfig()->main->logsPath) . DS . $name);
		if (!file_exists($path))
			throw new SimpleNotFoundException('Specified log doesn\'t exist');

		//load lines
		$lines = file_get_contents($path);
		$lines = trim($lines);
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

		return $this->pager->serialize($lines, $lineCount);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			$this->pager->getRequiredArguments(),
			JobArgs::ARG_LOG_ID,
			JobArgs::ARG_QUERY);
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(Privilege::ViewLog);
	}
}
