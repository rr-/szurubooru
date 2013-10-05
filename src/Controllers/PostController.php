<?php
class PostController
{
	/**
	* @route /posts
	* @route /posts/{query}
	* @validate query .*
	*/
	public function listAction($query = null)
	{
		$this->context->subTitle = 'browsing posts';
		throw new Exception('Not implemented');
	}

	/**
	* @route /post/upload
	*/
	public function uploadAction()
	{
		$this->context->subTitle = 'upload';
		throw new Exception('Not implemented');
	}

	/**
	* @route /post/{id}
	*/
	public function showAction($id)
	{
		$this->context->subTitle = 'showing @' . $id;
		throw new Exception('Not implemented');
	}
}
