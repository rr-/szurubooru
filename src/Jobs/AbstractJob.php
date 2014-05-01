<?php
abstract class AbstractJob
{
	public abstract function execute($arguments);

	public abstract function requiresAuthentication();
	public abstract function requiresConfirmedEmail();
	public abstract function requiresPrivilege();
}
