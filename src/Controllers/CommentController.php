<?php
class CommentController
{
	/**
	* @route /comments
	*/
	public function listAction()
	{
		$this->context->activeSection = 'comments';
		$this->context->subTitle = 'comments';
		throw new SimpleException('Not implemented');
	}
}
