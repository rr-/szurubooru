<?php
class LogController extends AbstractController
{
	public function listView()
	{
		$ret = Api::run(new ListLogsJob(), []);
		Core::getContext()->transport->logs = $ret;
		$this->renderView('log-list');
	}

	public function logView($name, $page = 1, $filter = '')
	{
		//redirect requests in form of ?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if ($formQuery !== null)
		{
			$this->redirect(\Chibi\Router::linkTo(
				['LogController', 'logView'],
				[
					'name' => $name,
					'filter' => $formQuery,
					'page' => 1
				]));
			return;
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

		$context = Core::getContext();
		$context->transport->paginator = $ret;
		$context->transport->lines = $lines;
		$context->transport->filter = $filter;
		$context->transport->name = $name;
		$this->renderView('log-view');
	}
}
