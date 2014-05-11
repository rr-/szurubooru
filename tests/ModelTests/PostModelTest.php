<?php
class PostModelTest extends AbstractTest
{
	public function testFeaturedPostRetrieval()
	{
		$post = $this->assert->doesNotThrow(function()
		{
			return PostModel::getFeaturedPost();
		});

		$this->assert->areEqual(null, $post);
	}

	public function testFeaturingNoPost()
	{
		PostModel::featureRandomPost();

		$post = $this->assert->doesNotThrow(function()
		{
			return PostModel::getFeaturedPost();
		});

		$this->assert->areEqual(null, $post);
	}

	public function testFeaturingRandomPost()
	{
		$post = $this->mockPost(Auth::getCurrentUser());

		PostModel::featureRandomPost();

		$this->assert->areEqual($post->getId(), (int) PropertyModel::get(PropertyModel::FeaturedPostId));
	}

	public function testFeaturingIllegalPosts()
	{
		$posts = [];
		foreach (range(0, 5) as $i)
			$posts []= $this->mockPost(Auth::getCurrentUser());
		$posts[0]->setSafety(new PostSafety(PostSafety::Sketchy));
		$posts[1]->setSafety(new PostSafety(PostSafety::Sketchy));
		$posts[2]->setHidden(true);
		$posts[3]->setType(new PostType(PostType::Youtube));
		$posts[4]->setType(new PostType(PostType::Flash));
		$posts[5]->setType(new PostType(PostType::Video));
		foreach ($posts as $post)
			PostModel::save($post);

		PostModel::featureRandomPost();

		$this->assert->areEqual(null, PropertyModel::get(PropertyModel::FeaturedPostId));
	}

	public function testAutoFeaturingFirstTime()
	{
		$this->mockPost(Auth::getCurrentUser());

		$this->assert->doesNotThrow(function()
		{
			PostModel::featureRandomPostIfNecessary();
		});

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}

	public function testAutoFeaturingTooSoon()
	{
		$this->mockPost(Auth::getCurrentUser());

		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		$this->assert->isFalse(PostModel::featureRandomPostIfNecessary());

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}

	public function testAutoFeaturingOutdated()
	{
		$post = $this->mockPost(Auth::getCurrentUser());
		$minTimestamp = getConfig()->misc->featuredPostMaxDays * 24 * 3600;

		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		PropertyModel::set(PropertyModel::FeaturedPostUnixTime, time() - $minTimestamp - 1);
		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		PropertyModel::set(PropertyModel::FeaturedPostUnixTime, time() - $minTimestamp + 1);
		$this->assert->isFalse(PostModel::featureRandomPostIfNecessary());

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}

	public function testAutoFeaturingDeletedPost()
	{
		$post = $this->mockPost(Auth::getCurrentUser());

		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		$this->assert->isNotNull(PostModel::getFeaturedPost());
		PostModel::remove($post);
		$anotherPost = $this->mockPost(Auth::getCurrentUser());
		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}
}
