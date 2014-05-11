<?php
class LogController
{
	public function listView()
	{
		$ret = Api::run(new ListLogsJob(), []);
		getContext()->transport->logs = $ret;
	}

	public function logView($name, $page = 1, $filter = '')
	{
		$context = getContext();
		$context->viewName = 'log-view';

		//redirect requests in form of ?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			\Chibi\Util\Url::forward(
				\Chibi\Router::linkTo(
					['LogController', 'logView'],
					[
						'name' => $name,
						'filter' => $formQuery,
						'page' => 1
					]));
			exit;
		}

		$ret = Api::run(
			new GetLogJob(),
			[
				JobArgs::ARG_PAGE_NUMBER => $page,
				JobArgs::ARG_LOG_ID => $name,
				JobArgs::ARG_QUERY => $filter,
			]);

		//stylize important lines
		$lines = $ret->entities;
		foreach ($lines as &$line)
			if (strpos($line, 'flag') !== false)
				$line = '**' . $line . '**';
		unset($line);

		$lines = join(PHP_EOL, $lines);
		$lines = TextHelper::parseMarkdown($lines, true);
		$lines = trim($lines);

		$context->transport->paginator = $ret;
		$context->transport->lines = $lines;
		$context->transport->filter = $filter;
		$context->transport->name = $name;
	}
}
