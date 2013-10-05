<?php
class IndexController
{
	/**
	* @route /
	* @route /index
	*/
	public function indexAction()
	{
		$this->context->subTitle = 'home';
	}
}
