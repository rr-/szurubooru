<?php
class EditPostRelationsJobTest extends AbstractTest
{
	public function testEditing()
	{
		$this->grantAccess('editPostRelations');

		list ($basePost, $post1, $post2)
			= $this->postMocker->mockMultiple(3);

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post1, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_POST_REVISION => $basePost->getRevision(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post1->getId(),
						$post2->getId(),
					]
				]);
		});

		$this->assert->areEqual(2, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[1]->getId());
	}

	public function testBothDirections()
	{
		$this->grantAccess('editPostRelations');

		list ($basePost, $post1, $post2)
			= $this->postMocker->mockMultiple(3);

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post1, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_POST_REVISION => $basePost->getRevision(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post1->getId(),
						$post2->getId(),
					]
				]);
		});

		$post1 = PostModel::getById($post1->getId());
		$post2 = PostModel::getById($post2->getId());

		$this->assert->areEqual(2, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[1]->getId());

		$this->assert->areEqual(1, count($post1->getRelations()));
		$this->assert->areEqual($basePost->getId(), $post1->getRelations()[0]->getId());

		$this->assert->areEqual(1, count($post2->getRelations()));
		$this->assert->areEqual($basePost->getId(), $post2->getRelations()[0]->getId());
	}

	public function testRelationsToItself()
	{
		$this->grantAccess('editPostRelations');

		list ($basePost, $post1, $post2)
			= $this->postMocker->mockMultiple(3);

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post1, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_POST_REVISION => $basePost->getRevision(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post1->getId(),
						$basePost->getId(),
						$post2->getId(),
					]
				]);
		});

		$this->assert->areEqual(2, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[1]->getId());
	}

	public function testOverwriting()
	{
		$this->grantAccess('editPostRelations');

		list ($basePost, $post1, $post2)
			= $this->postMocker->mockMultiple(3);

		$basePost->setRelations([$post1]);
		PostModel::save($basePost);

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());

		$basePost = $this->assert->doesNotThrow(function() use ($basePost, $post2)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_POST_REVISION => $basePost->getRevision(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
						$post2->getId(),
					]
				]);
		});

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post2->getId(), $basePost->getRelations()[0]->getId());
	}

	public function testOverwritingEmpty()
	{
		$this->grantAccess('editPostRelations');

		list ($basePost, $post1, $post2)
			= $this->postMocker->mockMultiple(3);

		$basePost->setRelations([$post1]);
		PostModel::save($basePost);

		$this->assert->areEqual(1, count($basePost->getRelations()));
		$this->assert->areEqual($post1->getId(), $basePost->getRelations()[0]->getId());

		$basePost = $this->assert->doesNotThrow(function() use ($basePost)
		{
			return Api::run(
				new EditPostRelationsJob(),
				[
					JobArgs::ARG_POST_ID => $basePost->getId(),
					JobArgs::ARG_POST_REVISION => $basePost->getRevision(),
					JobArgs::ARG_NEW_RELATED_POST_IDS =>
					[
					]
				]);
		});

		$basePost = PostModel::getById($basePost->getId());
		$this->assert->areEqual(0, count($basePost->getRelations()));
	}
}
