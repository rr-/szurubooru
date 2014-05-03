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
				GetLogJob::PAGE_NUMBER => $page,
				GetLogJob::LOG_ID => $name,
				GetLogJob::QUERY => $filter,
			]);

		//stylize important lines
		foreach ($ret->lines as &$line)
			if (strpos($line, 'flag') !== false)
				$line = '**' . $line . '**';
		unset($line);

		$ret->lines = join(PHP_EOL, $ret->lines);
		$ret->lines = TextHelper::parseMarkdown($ret->lines, true);
		$ret->lines = trim($ret->lines);

		$context->transport->paginator = new StdClass;
		$context->transport->paginator->page = $ret->page;
		$context->transport->paginator->pageCount = $ret->pageCount;
		$context->transport->paginator->entityCount = $ret->lineCount;
		$context->transport->paginator->entities = $ret->lines;
		$context->transport->lines = $ret->lines;
		$context->transport->filter = $filter;
		$context->transport->name = $name;
	}
}
