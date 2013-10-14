<?php
class UserController
{
	private static function locateUser($key)
	{
		$user = R::findOne('user', 'name = ?', [$key]);
		if (!$user)
			throw new SimpleException('Invalid user name "' . $key . '"');
		return $user;
	}



	/**
	* @route /users
	*/
	public function listAction()
	{
		$this->context->subTitle = 'users';
		throw new SimpleException('Not implemented');
	}



	/**
	* @route /user/{name}
	* @route /user/{name}/{tab}/{page}
	* @validate name [^\/]+
	* @validate tab favs|uploads
	* @validate page \d*
	*/
	public function viewAction($name, $tab, $page)
	{
		$this->context->stylesheets []= 'user-view.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';
		$this->context->subTitle = $name;
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewUser);

		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$user = self::locateUser($name);
		if ($tab === null)
			$tab = 'favs';
		if ($page === null)
			$page = 1;

		$buildDbQuery = function($dbQuery, $user, $tab)
		{
			$dbQuery->from('post');


			/* safety */
			$allowedSafety = array_filter(PostSafety::getAll(), function($safety)
			{
				return PrivilegesHelper::confirm($this->context->user, Privilege::ListPosts, PostSafety::toString($safety)) and
					$this->context->user->hasEnabledSafety($safety);
			});
			$dbQuery->where('safety IN (' . R::genSlots($allowedSafety) . ')');
			foreach ($allowedSafety as $s)
				$dbQuery->put($s);


			/* hidden */
			if (!PrivilegesHelper::confirm($this->context->user, Privilege::ListPosts, 'hidden'))
				$dbQuery->andNot('hidden');


			/* tab */
			switch ($tab)
			{
				case 'uploads':
					$dbQuery
						->and('uploader_id = ?')
						->put($user->id);
					break;
				case 'favs':
					$dbQuery
						->and()
						->exists()
						->open()
						->select('1')
						->from('favoritee')
						->where('post_id = post.id')
						->and('favoritee.user_id = ?')
						->put($user->id)
						->close();
					break;
			}
		};

		$countDbQuery = R::$f->begin()->select('COUNT(*)')->as('count');
		$buildDbQuery($countDbQuery, $user, $tab);
		$postCount = intval($countDbQuery->get('row')['count']);
		$pageCount = ceil($postCount / $postsPerPage);
		$page = max(1, min($pageCount, $page));

		$searchDbQuery = R::$f->begin()->select('*');
		$buildDbQuery($searchDbQuery, $user, $tab);
		$searchDbQuery->orderBy('id DESC')
			->limit('?')
			->put($postsPerPage)
			->offset('?')
			->put(($page - 1) * $postsPerPage);

		$posts = $searchDbQuery->get();
		$this->context->transport->user = $user;
		$this->context->transport->tab = $tab;
		$this->context->transport->page = $page;
		$this->context->transport->postCount = $postCount;
		$this->context->transport->pageCount = $pageCount;
		$this->context->transport->posts = $posts;
	}



	/**
	* @route /user/toggle-safety/{safety}
	*/
	public function toggleSafetyAction($safety)
	{
		if (!$this->context->loggedIn)
			throw new SimpleException('Not logged in');

		if (!in_array($safety, PostSafety::getAll()))
			throw new SimpleExcetpion('Invalid safety');

		$this->context->user->enableSafety($safety,
			!$this->context->user->hasEnabledSafety($safety));

		R::store($this->context->user);

		$this->context->transport->success = true;
	}
}
