<?php
class ApiPrivilegeTest extends AbstractFullApiTest
{
	public function testPrivilegeTesting()
	{
		$priv1 = new Privilege(Privilege::ViewPost, 'own');
		$priv2 = new Privilege(Privilege::ViewPost, 'own');
		$this->assert->areNotEqual($priv1, $priv2);
		$this->assert->areEquivalent($priv1, $priv2);
	}

	public function testRegularPrivileges()
	{
		$this->testRegularPrivilege(new AcceptUserRegistrationJob(), Privilege::AcceptUserRegistration);
		$this->testRegularPrivilege(new ActivateUserEmailJob(), null);
		$this->testRegularPrivilege(new AddCommentJob(), Privilege::AddComment);
		$this->testRegularPrivilege(new PreviewCommentJob(), Privilege::AddComment);
		$this->testRegularPrivilege(new AddPostJob(), Privilege::AddPost);
		$this->testRegularPrivilege(new AddUserJob(), Privilege::RegisterAccount);
		$this->testRegularPrivilege(new EditUserJob(), null);
		$this->testRegularPrivilege(new GetLogJob(), Privilege::ViewLog);
		$this->testRegularPrivilege(new GetPropertyJob(), null);
		$this->testRegularPrivilege(new ListCommentsJob(), Privilege::ListComments);
		$this->testRegularPrivilege(new ListLogsJob(), Privilege::ListLogs);
		$this->testRegularPrivilege(new ListPostsJob(), Privilege::ListPosts);
		$this->testRegularPrivilege(new ListRelatedTagsJob(), Privilege::ListTags);
		$this->testRegularPrivilege(new ListTagsJob(), Privilege::ListTags);
		$this->testRegularPrivilege(new ListUsersJob(), Privilege::ListUsers);
		$this->testRegularPrivilege(new PasswordResetJob(), null);
		$this->testRegularPrivilege(new MergeTagsJob(), Privilege::MergeTags);
		$this->testRegularPrivilege(new RenameTagsJob(), Privilege::RenameTags);
	}

	protected function testRegularPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual($expectedPrivilege, $job->getRequiredMainPrivilege());
		$this->assert->isNull($job->getRequiredSubPrivileges());
	}

	public function testDynamicPostPrivileges()
	{
		$this->login($this->userMocker->mockSingle());

		$this->testDynamicPostPrivilege(new DeletePostJob(), Privilege::DeletePost);
		$this->testDynamicPostPrivilege(new EditPostJob(), Privilege::EditPost);
		$this->testDynamicPostPrivilege(new EditPostContentJob(), Privilege::EditPostContent);
		$this->testDynamicPostPrivilege(new EditPostRelationsJob(), Privilege::EditPostRelations);
		$this->testDynamicPostPrivilege(new EditPostSafetyJob(), Privilege::EditPostSafety);
		$this->testDynamicPostPrivilege(new EditPostSourceJob(), Privilege::EditPostSource);
		$this->testDynamicPostPrivilege(new EditPostTagsJob(), Privilege::EditPostTags);
		$this->testDynamicPostPrivilege(new EditPostThumbnailJob(), Privilege::EditPostThumbnail);

		$ctx = function($job)
		{
			$job->setContext(AbstractJob::CONTEXT_BATCH_ADD);
			return $job;
		};
		$this->testDynamicPostPrivilege($ctx(new EditPostContentJob), Privilege::AddPostContent);
		$this->testDynamicPostPrivilege($ctx(new EditPostRelationsJob), Privilege::AddPostRelations);
		$this->testDynamicPostPrivilege($ctx(new EditPostSafetyJob), Privilege::AddPostSafety);
		$this->testDynamicPostPrivilege($ctx(new EditPostSourceJob), Privilege::AddPostSource);
		$this->testDynamicPostPrivilege($ctx(new EditPostTagsJob), Privilege::AddPostTags);
		$this->testDynamicPostPrivilege($ctx(new EditPostThumbnailJob), Privilege::AddPostThumbnail);

		$this->testDynamicPostPrivilege(new FeaturePostJob(), Privilege::FeaturePost);
		$this->testDynamicPostPrivilege(new FlagPostJob(), Privilege::FlagPost);
		$this->testDynamicPostPrivilege(new ScorePostJob(), Privilege::ScorePost);
		$this->testDynamicPostPrivilege(new TogglePostTagJob(), Privilege::EditPostTags);
		$this->testDynamicPostPrivilege(new TogglePostVisibilityJob(), Privilege::HidePost);
		$this->testDynamicPostPrivilege(new TogglePostFavoriteJob(), Privilege::FavoritePost);
	}

	protected function testDynamicPostPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual($expectedPrivilege, $job->getRequiredMainPrivilege());

		list ($ownPost, $otherPost) = $this->postMocker->mockMultiple(2);
		$ownPost->setUploader(Auth::getCurrentUser());
		$otherPost->setUploader($this->userMocker->mockSingle());
		PostModel::save([$ownPost, $otherPost]);

		$job->setArgument(JobArgs::ARG_POST_ID, $otherPost->getId());
		$job->prepare();
		$this->assert->areEqual('all', $job->getRequiredSubPrivileges());

		$job->setArgument(JobArgs::ARG_POST_ID, $ownPost->getId());
		$job->prepare();
		$this->assert->areEqual('own', $job->getRequiredSubPrivileges());
	}

	public function testDynamicPostRetrievalPrivileges()
	{
		$jobs =
		[
			new GetPostJob(),
			new GetPostContentJob(),
		];

		$post = $this->postMocker->mockSingle();

		foreach ($jobs as $job)
		{
			$this->testedJobs []= $job;

			$post->setHidden(true);
			PostModel::save($post);

			$job->setArgument(JobArgs::ARG_POST_ID, $post->getId());
			$job->setArgument(JobArgs::ARG_POST_NAME, $post->getName());
			$job->prepare();
			$this->assert->areEqual(Privilege::ViewPost, $job->getRequiredMainPrivilege());
			$sub = $job->getRequiredSubPrivileges();
			natcasesort($sub);
			$this->assert->areEquivalent(['hidden', 'safe'], $sub);
		}
	}

	public function testDynamicPostThumbnailPrivileges()
	{
		$job = new GetPostThumbnailJob();
		$this->testedJobs []= $job;
		$this->assert->isNull($job->getRequiredMainPrivilege());
	}

	public function testDynamicUserPrivileges()
	{
		$ownUser = $this->userMocker->mockSingle();
		$this->login($ownUser);

		$this->testDynamicUserPrivilege(new DeleteUserJob(), Privilege::DeleteUser);
		$this->testDynamicUserPrivilege(new EditUserAccessRankJob(), Privilege::EditUserAccessRank);
		$this->testDynamicUserPrivilege(new EditUserEmailJob(), Privilege::EditUserEmail);
		$this->testDynamicUserPrivilege(new EditUserNameJob(), Privilege::EditUserName);
		$this->testDynamicUserPrivilege(new EditUserPasswordJob(), Privilege::EditUserPassword);
		$this->testDynamicUserPrivilege(new EditUserSettingsJob(), Privilege::EditUserSettings);

		$ctx = function($job)
		{
			$job->setContext(AbstractJob::CONTEXT_BATCH_ADD);
			return $job;
		};
		$this->testDynamicUserPrivilege($ctx(new EditUserAccessRankJob()), Privilege::EditUserAccessRank);
		$this->testDynamicUserPrivilege($ctx(new EditUserEmailJob()), Privilege::RegisterAccount);
		$this->testDynamicUserPrivilege($ctx(new EditUserNameJob()), Privilege::RegisterAccount);
		$this->testDynamicUserPrivilege($ctx(new EditUserPasswordJob()), Privilege::RegisterAccount);
		$this->testDynamicUserPrivilege($ctx(new EditUserSettingsJob()), Privilege::EditUserSettings);

		$this->testDynamicUserPrivilege(new FlagUserJob(), Privilege::FlagUser);
		$this->testDynamicUserPrivilege(new GetUserJob(), Privilege::ViewUser);
		$this->testDynamicUserPrivilege(new GetUserSettingsJob(), Privilege::EditUserSettings);
		$this->testDynamicUserPrivilege(new ToggleUserBanJob(), Privilege::BanUser);
	}

	protected function testDynamicUserPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual($expectedPrivilege, $job->getRequiredMainPrivilege());

		$ownUser = Auth::getCurrentUser();

		$otherUser = $this->userMocker->mockSingle();
		$otherUser->setName('dummy' . uniqid());
		UserModel::save($otherUser);

		$job->setArgument(JobArgs::ARG_USER_NAME, $ownUser->getName());
		$job->prepare();
		$this->assert->areEqual('own', $job->getRequiredSubPrivileges());

		$job->setArgument(JobArgs::ARG_USER_NAME, $otherUser->getName());
		$job->prepare();
		$this->assert->areEqual('all', $job->getRequiredSubPrivileges());
	}

	public function testDynamicCommentPrivileges()
	{
		$this->login($this->userMocker->mockSingle());

		$this->testDynamicCommentPrivilege(new DeleteCommentJob(), Privilege::DeleteComment);
		$this->testDynamicCommentPrivilege(new EditCommentJob(), Privilege::EditComment);
	}

	protected function testDynamicCommentPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual($expectedPrivilege, $job->getRequiredMainPrivilege());

		list ($ownComment, $otherComment) = $this->commentMocker->mockMultiple(2);
		$ownComment->setCommenter(Auth::getCurrentUser());
		$otherComment->setCommenter($this->userMocker->mockSingle());
		CommentModel::save([$ownComment, $otherComment]);

		$job->setArgument(JobArgs::ARG_COMMENT_ID, $ownComment->getId());
		$job->prepare();
		$this->assert->areEqual('own', $job->getRequiredSubPrivileges());

		$job->setArgument(JobArgs::ARG_COMMENT_ID, $otherComment->getId());
		$job->prepare();
		$this->assert->areEqual('all', $job->getRequiredSubPrivileges());
	}

	public function testPrivilegeEnforcing()
	{
		$post = $this->postMocker->mockSingle();
		Core::getConfig()->registration->needEmailForCommenting = false;

		$this->assert->throws(function() use ($post)
		{
			Api::run(
				new AddCommentJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TEXT => 'alohaaa',
				]);
		}, 'Insufficient privileges');
	}

	public function testComplexPrivilegeEnforcing()
	{
		$post = $this->postMocker->mockSingle();
		Core::getConfig()->registration->needEmailForCommenting = false;
		$this->grantAccess('editPost.own');
		$this->grantAccess('editPostTags.own');
		$this->revokeAccess('editPost.all');
		$this->revokeAccess('editPostTags.all');
		$user = $this->userMocker->mockSingle();
		$this->login($user);

		$this->assert->throws(function() use ($post)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => ['test1', 'test2'],
				]);
		}, 'Insufficient privileges');

		$post->setUploader($user);
		PostModel::save($post);

		$this->assert->doesNotThrow(function() use ($post)
		{
			Api::run(
				new EditPostTagsJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TAG_NAMES => ['test1', 'test2'],
				]);
		});
	}
}
