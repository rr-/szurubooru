<?php
class SimpleException extends Exception
{
	public function __construct()
	{
		parent::__construct(call_user_func_array('sprintf', func_get_args()));
	}
}
