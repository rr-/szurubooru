<?php
class PostController extends AbstractController
{
	/**
	* @route /
	* @route /index
	*/
	public function searchAction()
	{
		$tp = new StdClass;
		$tp->posts = [];
		$tp->posts []= 1;
		$tp->posts []= 2;
		$this->context->transport = $tp;
	}
}
