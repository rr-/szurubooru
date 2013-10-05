<?php
class PostController extends AbstractController
{
	/**
	* @route /posts
	* @route /posts/{query}
	* @validate query .*
	*/
	public function listAction($query = null)
	{
		throw new Exception('Not implemented');
	}

	/**
	* @route /post/upload
	*/
	public function uploadAction()
	{
		throw new Exception('Not implemented');
	}

	/**
	* @route /post/{id}
	*/
	public function showAction($id)
	{
		throw new Exception('Not implemented');
	}
}
