<?php
class UserController
{
	/**
	* @route /users
	*/
	public function listAction()
	{
		$this->context->subTitle = 'users';
		throw new Exception('Not implemented');
	}

	/**
	* @route /user/{name}
	* @validate name [^\/]+
	*/
	public function showAction($name)
	{
		$this->context->subTitle = $name;
		throw new Exception('Not implemented');
	}
}
