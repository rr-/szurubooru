<?php
class IndexController
{
	/**
	* @route /
	* @route /index
	*/
	public function indexAction()
	{
		$this->context->activeSection = 'home';
		$this->context->subTitle = 'home';
	}
}
