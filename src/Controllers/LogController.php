<?php
class LogController
{
	public function listAction()
	{
		$context = getContext();
		Access::assert(Privilege::ListLogs);

		$path = TextHelper::absolutePath(getConfig()->main->logsPath);

		$logs = [];
		foreach (glob($path . DS . '*.log') as $log)
			$logs []= basename($log);

		usort($logs, function($a, $b)
		{
			return strnatcasecmp($b, $a); //reverse natcasesort
		});

		$context->transport->logs = $logs;
	}

	public function viewAction($name, $page = 1, $filter = '')
	{
		$context = getContext();
		//redirect requests in form of ?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			\Chibi\Util\Url::forward(
				\Chibi\Router::linkTo(
					['LogController', 'viewAction'],
					[
						'name' => $name,
						'filter' => $formQuery,
						'page' => 1
					]));
			return;
		}

		Access::assert(Privilege::ViewLog);

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

		//stylize important lines
		foreach ($lines as &$line)
			if (strpos($line, 'flag') !== false)
				$line = '**' . $line . '**';
		unset($line);

		$lines = join(PHP_EOL, $lines);
		$lines = TextHelper::parseMarkdown($lines, true);
		$lines = trim($lines);

		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $page;
		$context->transport->paginator->pageCount = $pageCount;
		$context->transport->paginator->entityCount = $lineCount;
		$context->transport->paginator->entities = $lines;
		$context->transport->lines = $lines;
		$context->transport->filter = $filter;
		$context->transport->name = $name;
	}
}
