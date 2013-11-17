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

		$path = $this->context->rootDir . DS . $this->config->main->logsPath;
		$path = TextHelper::cleanPath($path);

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
	* @validate name [0-9a-zA-Z._-]+
	*/
	public function viewAction($name)
	{
		$this->context->subTitle = 'logs (' . $name . ')';
		$this->context->stylesheets []= 'logs.css';
		$this->context->scripts []= 'logs.js';
		PrivilegesHelper::confirmWithException(Privilege::ViewLog);

		$name = str_replace(['/', '\\'], '', $name); //paranoia mode
		$path = $this->context->rootDir . DS . $this->config->main->logsPath . DS . $name;
		$path = TextHelper::cleanPath($path);
		if (!file_exists($path))
			throw new SimpleException('Specified log doesn\'t exist');

		$filter = InputHelper::get('filter');

		$lines = file_get_contents($path);
		$lines = explode(PHP_EOL, str_replace(["\r", "\n"], PHP_EOL, $lines));
		$lines = array_reverse($lines);
		if (!empty($filter))
			$lines = array_filter($lines, function($line) use ($filter) { return stripos($line, $filter) !== false; });
		$lines = join(PHP_EOL, $lines);
		$lines = TextHelper::parseMarkdown($lines);
		$lines = trim($lines);

		$this->context->transport->filter = $filter;
		$this->context->transport->name = $name;
		$this->context->transport->log = $lines;
	}
}
