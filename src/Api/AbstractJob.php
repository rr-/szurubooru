<?php
abstract class AbstractJob
{
	const COMMENT_ID = 'comment-id';
	const POST_ID = 'post-id';
	const POST_NAME = 'post-name';
	const TAG_NAME = 'tag-name';
	const TAG_NAMES = 'tags';
	const TEXT = 'text';
	const PAGE_NUMBER = 'page-number';
	const QUERY = 'query';
	const LOG_ID = 'log-id';
	const STATE = 'state';

	protected $arguments;

	public function prepare()
	{
	}

	public abstract function execute();

	public abstract function requiresAuthentication();
	public abstract function requiresConfirmedEmail();
	public abstract function requiresPrivilege();

	public function getArgument($key)
	{
		if (!$this->hasArgument($key))
			throw new ApiMissingArgumentException($key);

		return $this->arguments[$key];
	}

	public function getArguments()
	{
		return $this->arguments;
	}

	public function hasArgument($key)
	{
		return isset($this->arguments[$key]);
	}

	public function setArguments($arguments)
	{
		$this->arguments = $arguments;
	}
}
