<?php
interface IJob
{
	public function prepare();
	public function execute();

	public function getRequiredArguments();
	public function getRequiredPrivileges();
	public function isAuthenticationRequired();
	public function isConfirmedEmailRequired();

	public function getArgument($key);
	public function getArguments();
	public function hasArgument($key);
	public function setArgument($key, $value);
	public function setArguments(array $arguments);
}
