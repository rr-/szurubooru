<?php
abstract class AbstractJob
{
	public function prepare($arguments)
	{
	}

	public abstract function execute($arguments);

	public abstract function requiresAuthentication();
	public abstract function requiresConfirmedEmail();
	public abstract function requiresPrivilege();
}
