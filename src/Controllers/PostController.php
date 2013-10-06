<?php
class PostController
{
	public function workWrapper($callback)
	{
		$this->context->stylesheets []= 'jquery.tagit.css';
		$this->context->scripts []= 'jquery.tagit.js';
		$callback();
	}

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
		$this->context->stylesheets []= 'upload.css';
		$this->context->scripts []= 'upload.js';
		$this->context->subTitle = 'upload';

		PrivilegesHelper::confirmWithException($this->context->user, Privilege::UploadPost);

		if (isset($_FILES['file']))
		{
			$suppliedSafety = intval(InputHelper::get('safety'));
			if (!in_array($suppliedSafety, PostSafety::getAll()))
				throw new SimpleException('Invalid safety type "' . $suppliedSafety . '"');

			$suppliedTags = InputHelper::get('tags');
			$suppliedTags = preg_split('/[,;\s+]/', $suppliedTags);
			$suppliedTags = array_filter($suppliedTags);
			$suppliedTags = array_unique($suppliedTags);
			foreach ($suppliedTags as $tag)
				if (!preg_match('/^\w+$/i', $tag))
					throw new SimpleException('Invalid tag "' . $tag . '"');

			$suppliedFile = $_FILES['file'];

			switch ($suppliedFile['type'])
			{
				case 'image/gif':
				case 'image/png':
				case 'image/jpeg':
					$postType = PostType::Image;
					break;
				case 'application/x-shockwave-flash':
					$postType = PostType::Flash;
					break;
				default:
					throw new SimpleException('Invalid file type "' . $suppliedFile['type'] . '"');
			}

			//todo: find out duplicate files

			do
			{
				$name = md5(mt_rand() . uniqid());
				$path = $this->config->main->filesPath . DIRECTORY_SEPARATOR . $name;
			}
			while (file_exists($path));

			$dbTags = [];
			foreach ($suppliedTags as $tag)
			{
				$dbTag = R::findOne('tag', 'name = ?', [$tag]);
				if (!$dbTag)
				{
					$dbTag = R::dispense('tag');
					$dbTag->name = $tag;
					R::store($dbTag);
				}
				$dbTags []= $dbTag;
			}

			$dbPost = R::dispense('post');
			$dbPost->type = $postType;
			$dbPost->name = $name;
			$dbPost->mimeType = $suppliedFile['type'];
			$dbPost->safety = $suppliedSafety;
			$dbPost->sharedTag = $dbTags;

			move_uploaded_file($suppliedFile['tmp_name'], $path);
			R::store($dbPost);

			//todo: generate thumbnail

			$this->context->transport->success = true;
		}
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
