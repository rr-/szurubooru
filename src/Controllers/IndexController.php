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

	/**
	* @route /help
	*/
	public function helpAction()
	{
		$this->context->subTitle = 'help';
	}
}
