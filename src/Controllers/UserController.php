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
	* @route /user/{name}/ban
	* @validate name [^\/]+
	*/
	public function banAction($name)
	{
		$user = self::locateUser($name);
		$secondary = $user->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::BanUser, $secondary);
		$user->banned = true;
		R::store($user);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/{name}/unban
	* @validate name [^\/]+
	*/
	public function unbanAction($name)
	{
		$user = self::locateUser($name);
		$secondary = $user->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::BanUser, $secondary);
		$user->banned = false;
		R::store($user);
		$this->context->transport->success = true;
	}

	/**
	* @route /post/{name}/accept-registration
	* @validate name [^\/]+
	*/
	public function acceptRegistrationAction($name)
	{
		$user = self::locateUser($name);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::AcceptUserRegistration);
		$user->staff_confirmed = true;
		R::store($user);
		$this->context->transport->success = true;
	}




	/**
	* @route /user/{name}/delete
	* @validate name [^\/]+
	*/
	public function deleteAction($name)
	{
		$user = self::locateUser($name);
		$secondary = $user->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewUser, $secondary);
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::DeleteUser, $secondary);

		$this->context->handleExceptions = true;
		$this->context->transport->user = $user;
		$this->context->transport->tab = 'delete';
		$this->context->viewName = 'user-view';
		$this->context->stylesheets []= 'user-view.css';
		$this->context->subTitle = $name;

		$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');

		if (InputHelper::get('remove'))
		{
			if ($this->context->user->id == $user->id)
			{
				$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
				if ($suppliedPasswordHash != $user->pass_hash)
					throw new SimpleException('Must supply valid password');
			}
			$user->ownFavoritee = [];
			R::store($user);
			R::trash($user);
			\Chibi\UrlHelper::forward(\Chibi\UrlHelper::route('index', 'index'));
			$this->context->transport->success = true;
		}
	}



	/**
	* @route /user/{name}/edit
	* @validate name [^\/]+
	*/
	public function editAction($name)
	{
		try
		{

			$user = self::locateUser($name);
			$edited = false;
			$secondary = $user->id == $this->context->user->id ? 'own' : 'all';
			PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewUser, $secondary);

			$this->context->handleExceptions = true;
			$this->context->transport->user = $user;
			$this->context->transport->tab = 'edit';
			$this->context->viewName = 'user-view';
			$this->context->stylesheets []= 'user-view.css';
			$this->context->subTitle = $name;

			$this->context->suppliedCurrentPassword = $suppliedCurrentPassword = InputHelper::get('current-password');
			$this->context->suppliedName = $suppliedName = InputHelper::get('name');
			$this->context->suppliedPassword1 = $suppliedPassword1 = InputHelper::get('password1');
			$this->context->suppliedPassword2 = $suppliedPassword2 = InputHelper::get('password2');
			$this->context->suppliedEmail = $suppliedEmail = InputHelper::get('email');
			$this->context->suppliedAccessRank = $suppliedAccessRank = InputHelper::get('access-rank');
			$currentPasswordHash = $user->pass_hash;

			if ($suppliedName != '' and $suppliedName != $user->name)
			{
				PrivilegesHelper::confirmWithException($this->context->user, Privilege::ChangeUserName, $secondary);
				$suppliedName = Model_User::validateUserName($suppliedName);
				$user->name = $suppliedName;
				$edited = true;
			}

			if ($suppliedPassword1 != '')
			{
				PrivilegesHelper::confirmWithException($this->context->user, Privilege::ChangeUserPassword, $secondary);
				if ($suppliedPassword1 != $suppliedPassword2)
					throw new SimpleException('Specified passwords must be the same');
				$suppliedPassword = Model_User::validatePassword($suppliedPassword1);
				$user->pass_hash = Model_User::hashPassword($suppliedPassword, $user->pass_salt);
				$edited = true;
			}

			if ($suppliedEmail != '' and $suppliedEmail != $user->email)
			{
				PrivilegesHelper::confirmWithException($this->context->user, Privilege::ChangeUserEmail, $secondary);
				$suppliedEmail = Model_User::validateEmail($suppliedEmail);
				$user->email = $suppliedEmail;
				$edited = true;
			}

			if ($suppliedAccessRank != '' and $suppliedAccessRank != $user->access_rank)
			{
				PrivilegesHelper::confirmWithException($this->context->user, Privilege::ChangeUserAccessRank, $secondary);
				$suppliedAccessRank = Model_User::validateAccessRank($suppliedAccessRank);
				$user->access_rank = $suppliedAccessRank;
				$edited = true;
			}

			if ($edited)
			{
				if ($this->context->user->id == $user->id)
				{
					$suppliedPasswordHash = Model_User::hashPassword($suppliedCurrentPassword, $user->pass_salt);
					if ($suppliedPasswordHash != $currentPasswordHash)
						throw new SimpleException('Must supply valid current password');
				}
				R::store($user);
				$this->context->transport->success = true;
			}

		}
		catch (Exception $e)
		{
			$this->context->transport->user = self::locateUser($name);
			throw $e;
		}
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
		$postsPerPage = intval($this->config->browsing->postsPerPage);
		$user = self::locateUser($name);
		if ($tab === null)
			$tab = 'favs';
		if ($page === null)
			$page = 1;

		$secondary = $user->id == $this->context->user->id ? 'own' : 'all';
		PrivilegesHelper::confirmWithException($this->context->user, Privilege::ViewUser, $secondary);
		$this->context->stylesheets []= 'user-view.css';
		$this->context->stylesheets []= 'post-list.css';
		$this->context->stylesheets []= 'paginator.css';
		if ($this->config->browsing->endlessScrolling)
			$this->context->scripts []= 'paginator-endless.js';
		$this->context->subTitle = $name;

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
