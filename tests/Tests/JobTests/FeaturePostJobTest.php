<?php
class FeaturePostJobTest extends AbstractTest
{
	public function testFeaturing()
	{
		$this->grantAccess('featurePost');

		$user = $this->userMocker->mockSingle();
		$this->login($user);
		$posts = $this->postMocker->mockMultiple(2);

		$this->assert->doesNotThrow(function() use ($posts)
		{
			Api::run(
				new FeaturePostJob(),
				[
					JobArgs::ARG_POST_ID => $posts[1]->getId()
				]);
		});

		$this->assert->areEqual($posts[1]->getId(), PropertyModel::get(PropertyModel::FeaturedPostId));
		$this->assert->areEqual($user->getName(), PropertyModel::get(PropertyModel::FeaturedPostUserName));
		$this->assert->isNotNull(PropertyModel::get(PropertyModel::FeaturedPostUnixTime));
	}

	public function testAnonymousFeaturing()
	{
		$this->grantAccess('featurePost');

		$this->login($this->userMocker->mockSingle());
		$posts = $this->postMocker->mockMultiple(2);

		$this->assert->doesNotThrow(function() use ($posts)
		{
			Api::run(
				new FeaturePostJob(),
				[
					JobArgs::ARG_POST_ID => $posts[1]->getId(),
					JobArgs::ARG_ANONYMOUS => '1',
				]);
		});

		$this->assert->areEqual($posts[1]->getId(), PropertyModel::get(PropertyModel::FeaturedPostId));
		$this->assert->isNull(PropertyModel::get(PropertyModel::FeaturedPostUserName));
	}
}
