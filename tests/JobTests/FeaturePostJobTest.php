<?php
class FeaturePostJobTest extends AbstractTest
{
	public function testFeaturing()
	{
		$this->grantAccess('featurePost');

		$user = $this->mockUser();
		$this->login($user);
		$post1 = $this->mockPost($user);
		$post2 = $this->mockPost($user);

		$this->assert->doesNotThrow(function() use ($post2)
		{
			Api::run(
				new FeaturePostJob(),
				[
					JobArgs::ARG_POST_ID => $post2->getId()
				]);
		});

		$this->assert->areEqual($post2->getId(), PropertyModel::get(PropertyModel::FeaturedPostId));
		$this->assert->areEqual($user->getName(), PropertyModel::get(PropertyModel::FeaturedPostUserName));
		$this->assert->isNotNull(PropertyModel::get(PropertyModel::FeaturedPostUnixTime));
	}

	public function testAnonymousFeaturing()
	{
		$this->grantAccess('featurePost');

		$user = $this->mockUser();
		$this->login($user);
		$post1 = $this->mockPost($user);
		$post2 = $this->mockPost($user);

		$this->assert->doesNotThrow(function() use ($post2)
		{
			Api::run(
				new FeaturePostJob(),
				[
					JobArgs::ARG_POST_ID => $post2->getId(),
					JobArgs::ARG_ANONYMOUS => true,
				]);
		});

		$this->assert->areEqual($post2->getId(), PropertyModel::get(PropertyModel::FeaturedPostId));
		$this->assert->areEqual(null, PropertyModel::get(PropertyModel::FeaturedPostUserName));
	}
}
