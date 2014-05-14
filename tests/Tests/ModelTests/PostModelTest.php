<?php
class PostModelTest extends AbstractTest
{
	public function testFeaturedPostRetrieval()
	{
		$post = $this->assert->doesNotThrow(function()
		{
			return PostModel::getFeaturedPost();
		});

		$this->assert->isNull($post);
	}

	public function testFeaturingNoPost()
	{
		PostModel::featureRandomPost();

		$post = $this->assert->doesNotThrow(function()
		{
			return PostModel::getFeaturedPost();
		});

		$this->assert->isNull($post);
	}

	public function testFeaturingRandomPost()
	{
		$post = $this->postMocker->mockSingle();

		PostModel::featureRandomPost();

		$this->assert->areEqual($post->getId(), (int) PropertyModel::get(PropertyModel::FeaturedPostId));
	}

	public function testFeaturingIllegalPosts()
	{
		$posts = [];
		$posts = $this->postMocker->mockMultiple(6);
		$posts[0]->setSafety(new PostSafety(PostSafety::Sketchy));
		$posts[1]->setSafety(new PostSafety(PostSafety::Sketchy));
		$posts[2]->setHidden(true);
		$posts[3]->setType(new PostType(PostType::Youtube));
		$posts[4]->setType(new PostType(PostType::Flash));
		$posts[5]->setType(new PostType(PostType::Video));
		PostModel::save($posts);

		PostModel::featureRandomPost();

		$this->assert->isNull(PropertyModel::get(PropertyModel::FeaturedPostId));
	}

	public function testAutoFeaturingFirstTime()
	{
		$this->postMocker->mockSingle();

		$this->assert->doesNotThrow(function()
		{
			PostModel::featureRandomPostIfNecessary();
		});

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}

	public function testAutoFeaturingTooSoon()
	{
		$this->postMocker->mockSingle();

		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		$this->assert->isFalse(PostModel::featureRandomPostIfNecessary());

		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}

	public function testAutoFeaturingOutdated()
	{
		$post = $this->postMocker->mockSingle();
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
		$post = $this->postMocker->mockSingle();
		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		$this->assert->isNotNull(PostModel::getFeaturedPost());

		PostModel::remove($post);

		$anotherPost = $this->postMocker->mockSingle();
		$this->assert->isTrue(PostModel::featureRandomPostIfNecessary());
		$this->assert->isNotNull(PostModel::getFeaturedPost());
	}
}
