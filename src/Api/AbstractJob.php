<?php
abstract class AbstractJob
{
	const COMMENT_ID = 'comment-id';
	const LOG_ID = 'log-id';

	const POST_ENTITY = 'post';
	const POST_ID = 'post-id';
	const POST_NAME = 'post-name';

	const TAG_NAME = 'tag-name';
	const TAG_NAMES = 'tags';

	const USER_ENTITY = 'user';
	const USER_ID = 'user-id';
	const USER_NAME = 'user-name';

	const PAGE_NUMBER = 'page-number';
	const TEXT = 'text';
	const QUERY = 'query';
	const STATE = 'state';

	protected $arguments = [];

	public function prepare()
	{
	}

	public abstract function execute();

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}

	public function requiresPrivilege()
	{
		return false;
	}

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

	public function setArgument($key, $value)
	{
		$this->arguments[$key] = $value;
	}

	public function setArguments(array $arguments)
	{
		$this->arguments = $arguments;
	}
}
