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
		#redirect requests in form of /posts/?query=... to canonical address
		$formQuery = InputHelper::get('query');
		if (!empty($formQuery))
		{
			$url = \Chibi\UrlHelper::route('post', 'list', ['query' => $formQuery]);
			\Chibi\UrlHelper::forward($url);
			return;
		}

		$this->context->subTitle = 'browsing posts';
		$this->context->searchQuery = $query;
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

	/**
	* @route /favorites
	*/
	public function favoritesAction()
	{
		$this->listAction('favmin:1');
	}
}
