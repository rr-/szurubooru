<?php
class UserController extends AbstractController
{
	/**
	* @route /users
	*/
	public function listAction()
	{
		throw new Exception('Not implemented');
	}

	/**
	* @route /user/{name}
	* @validate name [^\/]+
	*/
	public function showAction($name)
	{
		throw new Exception('Not implemented');
	}
}
