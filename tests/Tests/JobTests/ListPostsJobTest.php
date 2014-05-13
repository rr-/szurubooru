<?php
class ListPostsJobTest extends AbstractTest
{
	public function testPaging()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);
		getConfig()->browsing->postsPerPage = 2;

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_PAGE_NUMBER => 2]);
		});

		$this->assert->areEqual(3, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(2, $ret->pageCount);
		$this->assert->areEqual(2, $ret->page);
	}

	public function testTooManyTokens()
	{
		$this->grantAccess('listPosts');

		$this->assert->throws(function()
		{
			Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 't1 t2 t3 t4 t5 t6 t7']);
		}, 'too many search tokens');
	}

	public function testAutomaticSafetyFilterOnlySafeEnabled()
	{
		$user = $this->userMocker->mockSingle();
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Safe), true);
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Sketchy), false);
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Unsafe), false);
		UserModel::save($user);
		$this->login($user);

		$this->grantAccess('listPosts.safe');
		$this->grantAccess('listPosts.sketchy');
		$this->revokeAccess('listPosts.unsafe');

		$posts = $this->preparePostsWithSafety();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual(1, count($ret->entities));
		$this->assert->areEqual(1, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);
		$this->assert->areEqual($posts[0]->getId(), $ret->entities[0]->getId());
	}

	public function testAutomaticSafetyFilterAllEnabled()
	{
		$user = $this->userMocker->mockSingle();
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Safe), true);
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Sketchy), true);
		$user->getSettings()->enableSafety(new PostSafety(PostSafety::Unsafe), true);
		UserModel::save($user);
		$this->login($user);

		$this->grantAccess('listPosts.safe');
		$this->grantAccess('listPosts.sketchy');
		$this->revokeAccess('listPosts.unsafe');

		$posts = $this->preparePostsWithSafety();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual(2, count($ret->entities));
		$this->assert->areEqual(1, $ret->pageCount);
		$this->assert->areEqual(1, $ret->page);
		$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());
		$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
	}

	public function testDislikedHiding()
	{
		$this->grantAccess('listPosts');
		$user = $this->userMocker->mockSingle();
		$user->getSettings()->enableHidingDislikedPosts(true);
		$post = $this->postMocker->mockSingle();
		$this->login($user);

		UserModel::updateUserScore($user, $post, -1);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(0, $ret->entityCount);
	}

	public function testDislikedShowing()
	{
		$this->grantAccess('listPosts');
		$user = $this->userMocker->mockSingle();
		$user->getSettings()->enableHidingDislikedPosts(false);
		$post = $this->postMocker->mockSingle();
		$this->login($user);

		UserModel::updateUserScore($user, $post, -1);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(1, $ret->entityCount);
	}

	public function testDislikedHidingFilterOverride()
	{
		$this->grantAccess('listPosts');
		$user = $this->userMocker->mockSingle();
		$user->getSettings()->enableHidingDislikedPosts(true);
		$post = $this->postMocker->mockSingle();
		$this->login($user);

		UserModel::updateUserScore($user, $post, -1);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:disliked']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
	}

	public function testHiddenHiding()
	{
		$this->grantAccess('listPosts');

		$post = $this->postMocker->mockSingle();
		$post->setHidden(true);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), []);
		});

		$this->assert->areEqual(0, $ret->entityCount);
	}

	public function testHiddenShowingWithoutAccess()
	{
		$this->grantAccess('listPosts');
		$this->revokeAccess('listPosts.hidden');

		$post = $this->postMocker->mockSingle();
		$post->setHidden(true);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:hidden']);
		});

		$this->assert->areEqual(0, $ret->entityCount);
	}

	public function testHiddenShowingWithAccess()
	{
		$this->grantAccess('listPosts');

		$post = $this->postMocker->mockSingle();
		$post->setHidden(true);
		PostModel::save($post);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:hidden']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
	}

	public function testIds()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);

		foreach (['id', 'ids'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1,3,5']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testFavs()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);
		$user = $this->userMocker->mockSingle();
		UserModel::addToUserFavorites($user, $posts[0]);
		UserModel::addToUserFavorites($user, $posts[2]);

		foreach (['fav', 'favs', 'favd'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias, $user)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':' . $user->getName()]);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testOwnFavs()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);
		$user = $this->userMocker->mockSingle();
		UserModel::addToUserFavorites($user, $posts[0]);
		UserModel::addToUserFavorites($user, $posts[2]);

		$this->login($user);

		foreach (['fav', 'favs', 'favd'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias, $user)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:' . $alias]);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testOwnLiked()
	{
		$this->grantAccess('listPosts');
		$user = $this->userMocker->mockSingle();
		$posts = $this->postMocker->mockMultiple(3);
		UserModel::updateUserScore($user, $posts[0], 1);
		UserModel::updateUserScore($user, $posts[2], 1);

		$this->login($user);

		foreach (['like', 'liked', 'likes'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias, $user)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:' . $alias]);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testUploads()
	{
		$this->grantAccess('listPosts');
		$users = $this->userMocker->mockMultiple(2);
		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setUploader($users[0]);
		$posts[1]->setUploader($users[1]);
		$posts[2]->setUploader($users[0]);
		PostModel::save($posts);

		foreach (['submit', 'upload', 'uploads', 'uploader', 'uploaded'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias, $users)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':' . $users[0]->getName()]);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testIdMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);

		foreach (['idmin', 'id_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':2']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}

		foreach (['idmax', 'id_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':2']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testScoreMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithScores();

		foreach (['scoremin', 'score_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}

		foreach (['scoremax', 'score_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testFavMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithFavs();

		foreach (['favmin', 'fav_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}

		foreach (['favmax', 'fav_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testCommentMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithComments();

		foreach (['commentmin', 'comment_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}

		foreach (['commentmax', 'comment_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testTagMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTags();

		foreach (['tagmin', 'tag_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':2']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}

		foreach (['tagmax', 'tag_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':2']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testDate()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithDates();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'date:1990-10-22']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
	}

	public function testDateMinMax()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithDates();

		foreach (['datemin', 'date_min'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1990-10-22']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
		}

		foreach (['datemax', 'date_max'] as $alias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($alias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $alias . ':1990-10-22']);
			});

			$this->assert->areEqual(2, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
			$this->assert->areEqual($posts[1]->getId(), $ret->entities[1]->getId());
		}
	}

	public function testInvalidSpecialToken()
	{
		$this->grantAccess('listPosts');

		$this->assert->throws(function()
		{
			Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'special:nonsense']);
		}, 'invalid');
	}

	public function testTypes()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTypes();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'type:video']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());

		foreach (['flash', 'swf'] as $typeAlias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($typeAlias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'type:' . $typeAlias]);
			});

			$this->assert->areEqual(1, $ret->entityCount);
			$this->assert->areEqual($posts[3]->getId(), $ret->entities[0]->getId());
		}

		foreach (['img', 'image'] as $typeAlias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($typeAlias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'type:' . $typeAlias]);
			});

			$this->assert->areEqual(1, $ret->entityCount);
			$this->assert->areEqual($posts[0]->getId(), $ret->entities[0]->getId());
		}

		foreach (['yt', 'youtube'] as $typeAlias)
		{
			$ret = $this->assert->doesNotThrow(function() use ($typeAlias)
			{
				return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'type:' . $typeAlias]);
			});

			$this->assert->areEqual(1, $ret->entityCount);
			$this->assert->areEqual($posts[2]->getId(), $ret->entities[0]->getId());
		}
	}

	public function testInvalidType()
	{
		$this->grantAccess('listPosts');

		$this->assert->throws(function()
		{
			Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'type:nonsense']);
		}, 'invalid');
	}

	public function testMultipleTags()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTags();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'tag1 tag2']);
		});

		$this->assert->areEqual(2, $ret->entityCount);
		$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());
		$this->assert->areEqual($posts[0]->getId(), $ret->entities[1]->getId());
	}

	public function testTagNegation()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTags();

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'tag1 tag2 -tag3']);
		});

		$this->assert->areEqual(1, $ret->entityCount);
		$this->assert->areEqual($posts[1]->getId(), $ret->entities[0]->getId());
	}

	public function testOrderById()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTags();

		$this->testOrder('order:id', [$posts[2], $posts[1], $posts[0]]);
	}

	public function testOrderByIdUsingAlternativeKeyword()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithTags();

		$this->testOrder('sort:id', [$posts[2], $posts[1], $posts[0]]);
	}

	public function testOrderByFavCount()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithFavs();

		foreach (['fav', 'favs', 'favcount', 'fav_count'] as $alias)
			$this->testOrder('order:' . $alias, [$posts[0], $posts[2], $posts[1]]);
	}

	public function testOrderByCommentCount()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithComments();

		foreach (['comment', 'comments', 'commentcount', 'comment_count'] as $alias)
			$this->testOrder('order:' . $alias, [$posts[0], $posts[2], $posts[1]]);
	}

	public function testOrderByScores()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithScores();

		$this->testOrder('order:score', [$posts[0], $posts[2], $posts[1]]);
	}

	public function testOrderByDates()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithDates();

		$this->testOrder('order:date', [$posts[0], $posts[2], $posts[1]]);
	}

	public function testOrderByCommentDates()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithComments();

		foreach (['commentdate', 'comment_date'] as $alias)
			$this->testOrder('order:' . $alias, [$posts[2], $posts[0], $posts[1]]);
	}

	public function testOrderByFavDates()
	{
		$this->grantAccess('listPosts');
		$posts = $this->preparePostsWithFavs();

		foreach (['favdate', 'fav_date'] as $alias)
			$this->testOrder('order:' . $alias, [$posts[2], $posts[0], $posts[1]]);
	}

	public function testOrderByFilesize()
	{
		$this->grantAccess('listPosts');
		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setFileSize(100);
		$posts[1]->setFileSize(50);
		$posts[2]->setFileSize(300);
		PostModel::save($posts);

		foreach (['filesize', 'file_size'] as $alias)
			$this->testOrder('order:' . $alias, [$posts[2], $posts[0], $posts[1]]);
	}

	public function testOrderByRandom()
	{
		$this->grantAccess('listPosts');
		$num = 15;
		$posts = $this->postMocker->mockMultiple($num);
		$expectedPostIdsSorted = range(1, $num);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'order:random']);
		});
		$postIds1 = array_map(function($x) { return $x->getId(); }, $ret->entities);

		$this->assert->areNotEquivalent($expectedPostIdsSorted, $postIds1);
		$postIds1Sorted = $postIds1 + [];
		sort($postIds1Sorted);
		$this->assert->areEquivalent($expectedPostIdsSorted, $postIds1Sorted);

		$ret = $this->assert->doesNotThrow(function()
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'order:random']);
		});
		$postIds2 = array_map(function($x) { return $x->getId(); }, $ret->entities);
		$this->assert->areEquivalent($postIds1, $postIds2);
	}


	public function testInvalidOrderToken()
	{
		$this->grantAccess('listPosts');

		$this->assert->throws(function()
		{
			Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => 'order:nonsense']);
		}, 'invalid');
	}


	private function testOrder($query, $expectedPosts)
	{
		$ret = $this->assert->doesNotThrow(function() use ($query)
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => $query]);
		});

		$this->assert->areEqual(count($expectedPosts), $ret->entityCount);
		foreach ($expectedPosts as $i => $expectedPost)
			$this->assert->areEqual($expectedPost->getId(), $ret->entities[$i]->getId());

		$expectedPosts = array_reverse($expectedPosts);

		$ret = $this->assert->doesNotThrow(function() use ($query)
		{
			return Api::run(new ListPostsJob(), [JobArgs::ARG_QUERY => '-' . $query]);
		});

		$this->assert->areEqual(count($expectedPosts), $ret->entityCount);
		foreach ($expectedPosts as $i => $expectedPost)
			$this->assert->areEqual($expectedPost->getId(), $ret->entities[$i]->getId());
	}

	private function preparePostsWithScores()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$users = $this->userMocker->mockMultiple(2);
		UserModel::updateUserScore($users[0], $posts[0], 1);
		UserModel::updateUserScore($users[1], $posts[0], 1);
		UserModel::updateUserScore($users[0], $posts[2], 1);
		return $posts;
	}

	private function preparePostsWithSafety()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setSafety(new PostSafety(PostSafety::Safe));
		$posts[1]->setSafety(new PostSafety(PostSafety::Sketchy));
		$posts[2]->setSafety(new PostSafety(PostSafety::Unsafe));
		return PostModel::save($posts);
	}

	private function preparePostsWithTypes()
	{
		$posts = $this->postMocker->mockMultiple(4);
		$posts[0]->setType(new PostType(PostType::Image));
		$posts[1]->setType(new PostType(PostType::Video));
		$posts[2]->setType(new PostType(PostType::Youtube));
		$posts[3]->setType(new PostType(PostType::Flash));
		return PostModel::save($posts);
	}

	private function preparePostsWithTags()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setTags(TagModel::spawnFromNames(['tag1', 'tag2', 'tag3']));
		$posts[1]->setTags(TagModel::spawnFromNames(['tag1', 'tag2']));
		$posts[2]->setTags(TagModel::spawnFromNames(['tag1']));
		return PostModel::save($posts);
	}

	private function preparePostsWithFavs()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$users = $this->userMocker->mockMultiple(2);
		UserModel::addToUserFavorites($users[0], $posts[0]);
		UserModel::addToUserFavorites($users[1], $posts[0]);
		UserModel::addToUserFavorites($users[0], $posts[2]);
		return $posts;
	}

	private function preparePostsWithComments()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$comment1 = CommentModel::spawn();
		$comment2 = CommentModel::spawn();
		$comment3 = CommentModel::spawn();
		$comment1->setPost($posts[0]);
		$comment2->setPost($posts[0]);
		$comment3->setPost($posts[2]);
		foreach ([$comment1, $comment2, $comment3] as $comment)
		{
			$comment->setText('alohaaa');
			CommentModel::save($comment);
		}
		return $posts;
	}

	private function preparePostsWithDates()
	{
		$posts = $this->postMocker->mockMultiple(3);
		$posts[0]->setCreationTime(mktime(0, 0, 0, 10, 23, 1990));
		$posts[1]->setCreationTime(mktime(0, 0, 0, 10, 21, 1990));
		$posts[2]->setCreationTime(mktime(0, 0, 0, 10, 22, 1990));
		return PostModel::save($posts);
	}
}
