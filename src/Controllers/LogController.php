<?php
class LogController
{
	/**
	* @route /logs
	*/
	public function listAction()
	{
		$this->context->subTitle = 'latest logs';
		PrivilegesHelper::confirmWithException(Privilege::ListLogs);

		$path = TextHelper::absolutePath($this->config->main->logsPath);

		$logs = [];
		foreach (glob($path . DS . '*.log') as $log)
			$logs []= basename($log);

		usort($logs, function($a, $b)
		{
			return strnatcasecmp($b, $a); //reverse natcasesort
		});

		$this->context->transport->logs = $logs;
	}

	/**
	* @route /log/{name}
	* @route /log/{name}/{page}
	* @route /log/{name}/{page}/{filter}
	* @validate name [0-9a-zA-Z._-]+
	* @validate page \d*
	* @validate filter .*
	*/
	public function viewAction($name, $page = 1, $filter = '')
	{
		//redirect requests in form of ?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			\Chibi\UrlHelper::forward(
				\Chibi\UrlHelper::route(
					'log',
					'view',
					[
						'name' => $name,
						'filter' => $formQuery,
						'page' => 1
					]));
			return;
		}

		$this->context->subTitle = 'logs (' . $name . ')';
		$this->context->stylesheets []= 'logs.css';
		$this->context->stylesheets []= 'paginator.css';
		$this->context->scripts []= 'logs.js';
		if ($this->context->user->hasEnabledEndlessScrolling())
			$this->context->scripts []= 'paginator-endless.js';
		PrivilegesHelper::confirmWithException(Privilege::ViewLog);

		//parse input
		$page = max(1, intval($page));
		$name = str_replace(['/', '\\'], '', $name); //paranoia mode
		$path = TextHelper::absolutePath($this->config->main->logsPath . DS . $name);
		if (!file_exists($path))
			throw new SimpleException('Specified log doesn\'t exist');

		//load lines
		$lines = file_get_contents($path);
		$lines = explode(PHP_EOL, str_replace(["\r", "\n"], PHP_EOL, $lines));
		$lines = array_reverse($lines);

		if (!empty($filter))
			$lines = array_filter($lines, function($line) use ($filter) { return stripos($line, $filter) !== false; });

		$lineCount = count($lines);
		$logsPerPage = intval($this->config->browsing->logsPerPage);
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

		$this->context->transport->paginator = new StdClass;
		$this->context->transport->paginator->page = $page;
		$this->context->transport->paginator->pageCount = $pageCount;
		$this->context->transport->paginator->entityCount = $lineCount;
		$this->context->transport->paginator->entities = $lines;
		$this->context->transport->lines = $lines;
		$this->context->transport->filter = $filter;
		$this->context->transport->name = $name;
	}
}
