<?php
class TogglePostFavoriteJobTest extends AbstractTest
{
	public function testFaving()
	{
		$this->grantAccess('favoritePost');
		$post = $this->postMocker->mockSingle();
		$user = $this->userMocker->mockSingle();
		$this->login($user);

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->assert->areEqual(1, $post->getScore());
		$this->assert->areEqual(1, $post->getFavoriteCount());
		$this->assert->areEqual(1, $user->getFavoriteCount());
		$this->assert->areEqual(1, $user->getScore($post));
		$this->assert->areEqual(true, $user->hasFavorited($post));
	}

	public function testDefaving()
	{
		$this->grantAccess('favoritePost');
		$post = $this->postMocker->mockSingle();
		$user = $this->userMocker->mockSingle();
		$this->login($user);

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 0,
				]);
		});

		$this->assert->areEqual(1, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());
		$this->assert->areEqual(0, $user->getFavoriteCount());
		$this->assert->areEqual(1, $user->getScore($post));
		$this->assert->areEqual(false, $user->hasFavorited($post));
	}

	public function testFavingTwoPeople()
	{
		$this->grantAccess('favoritePost');
		$users = $this->userMocker->mockMultiple(2);
		$post = $this->postMocker->mockSingle();

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$this->login($users[0]);
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->login($users[1]);
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_STATE => 1,
				]);
		});

		$this->assert->areEqual(2, $post->getScore());
		$this->assert->areEqual(2, $post->getFavoriteCount());
		$this->assert->areEqual(1, $users[0]->getFavoriteCount());
		$this->assert->areEqual(1, $users[0]->getScore($post));
		$this->assert->areEqual(1, $users[1]->getFavoriteCount());
		$this->assert->areEqual(1, $users[1]->getScore($post));
		$this->assert->areEqual(true, $users[0]->hasFavorited($post));
		$this->assert->areEqual(true, $users[1]->hasFavorited($post));
	}
}
