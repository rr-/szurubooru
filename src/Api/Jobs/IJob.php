<?php
interface IJob
{
	const CONTEXT_NORMAL = 1;
	const CONTEXT_BATCH_EDIT = 2;
	const CONTEXT_BATCH_ADD = 3;

	public function prepare();
	public function execute();

	public function getContext();
	public function setContext($context);

	public function getRequiredArguments();
	public function getRequiredMainPrivilege();
	public function getRequiredSubPrivileges();
	public function isAuthenticationRequired();
	public function isConfirmedEmailRequired();
	public function isAvailableToPublic();

	public function getArgument($key);
	public function getArguments();
	public function hasArgument($key);
	public function setArgument($key, $value);
	public function setArguments(array $arguments);
}
