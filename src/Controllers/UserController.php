<?php
class UserController
{
	/**
	* @route /users
	*/
	public function listAction()
	{
		$this->context->subTitle = 'users';
		throw new SimpleException('Not implemented');
	}

	/**
	* @route /user/{name}
	* @validate name [^\/]+
	*/
	public function viewAction($name)
	{
		$this->context->subTitle = $name;
		throw new SimpleException('Not implemented');
	}
}
