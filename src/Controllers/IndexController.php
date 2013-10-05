<?php
class IndexController extends AbstractController
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
