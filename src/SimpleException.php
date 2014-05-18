<?php
class SimpleException extends Exception
{
	public function __construct()
	{
		parent::__construct(func_num_args() > 1
			? call_user_func_array('sprintf', func_get_args())
			: func_get_args()[0]);
	}
}
