<?php
class CommentController extends AbstractController
{
	/**
	* @route /comments
	*/
	public function listAction()
	{
		$this->context->subTitle = 'comments';
		throw new Exception('Not implemented');
	}
}
