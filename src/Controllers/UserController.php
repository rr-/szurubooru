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

	/**
	* @route /user/toggle-safety/{safety}
	*/
	public function toggleSafetyAction($safety)
	{
		if (!$this->context->loggedIn)
			throw new SimpleException('Not logged in');

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleExcetpion('Invalid safety');

		$this->context->user->enableSafety($safety,
			!$this->context->user->hasEnabledSafety($safety));

		R::store($this->context->user);

		$this->context->transport->success = true;
	}
}
