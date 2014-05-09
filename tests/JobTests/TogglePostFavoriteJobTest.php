<?php
class TogglePostFavoriteJobTest extends AbstractTest
{
	public function testFaving()
	{
		$this->grantAccess('favoritePost');
		$user = $this->mockUser();
		$this->login($user);
		$post = $this->mockPost($user);

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					ScorePostJob::POST_ID => $post->getId(),
					ScorePostJob::STATE => 1,
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
		$user = $this->mockUser();
		$this->login($user);
		$post = $this->mockPost($user);

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					ScorePostJob::POST_ID => $post->getId(),
					ScorePostJob::STATE => 1,
				]);
		});

		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					ScorePostJob::POST_ID => $post->getId(),
					ScorePostJob::STATE => 0,
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
		$user1 = $this->mockUser();
		$user2 = $this->mockUser();
		$post = $this->mockPost($user1);

		$this->assert->areEqual(0, $post->getScore());
		$this->assert->areEqual(0, $post->getFavoriteCount());

		$this->login($user1);
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					ScorePostJob::POST_ID => $post->getId(),
					ScorePostJob::STATE => 1,
				]);
		});

		$this->login($user2);
		$post = $this->assert->doesNotThrow(function() use ($post)
		{
			return Api::run(
				new TogglePostFavoriteJob(),
				[
					ScorePostJob::POST_ID => $post->getId(),
					ScorePostJob::STATE => 1,
				]);
		});

		$this->assert->areEqual(2, $post->getScore());
		$this->assert->areEqual(2, $post->getFavoriteCount());
		$this->assert->areEqual(1, $user1->getFavoriteCount());
		$this->assert->areEqual(1, $user1->getScore($post));
		$this->assert->areEqual(1, $user2->getFavoriteCount());
		$this->assert->areEqual(1, $user2->getScore($post));
		$this->assert->areEqual(true, $user1->hasFavorited($post));
		$this->assert->areEqual(true, $user2->hasFavorited($post));
	}
}
