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
		$this->testRegularPrivilege(new AcceptUserRegistrationJob(), new Privilege(Privilege::AcceptUserRegistration));
		$this->testRegularPrivilege(new ActivateUserEmailJob(), false);
		$this->testRegularPrivilege(new AddCommentJob(), new Privilege(Privilege::AddComment));
		$this->testRegularPrivilege(new PreviewCommentJob(), new Privilege(Privilege::AddComment));
		$this->testRegularPrivilege(new AddPostJob(), new Privilege(Privilege::UploadPost));
		$this->testRegularPrivilege(new AddUserJob(), new Privilege(Privilege::RegisterAccount));
		$this->testRegularPrivilege(new EditPostJob(), false);
		$this->testRegularPrivilege(new EditUserJob(), false);
		$this->testRegularPrivilege(new GetLogJob(), new Privilege(Privilege::ViewLog));
		$this->testRegularPrivilege(new ListCommentsJob(), new Privilege(Privilege::ListComments));
		$this->testRegularPrivilege(new ListLogsJob(), new Privilege(Privilege::ListLogs));
		$this->testRegularPrivilege(new ListPostsJob(), new Privilege(Privilege::ListPosts));
		$this->testRegularPrivilege(new ListRelatedTagsJob(), new Privilege(Privilege::ListTags));
		$this->testRegularPrivilege(new ListTagsJob(), new Privilege(Privilege::ListTags));
		$this->testRegularPrivilege(new ListUsersJob(), new Privilege(Privilege::ListUsers));
		$this->testRegularPrivilege(new PasswordResetJob(), false);
		$this->testRegularPrivilege(new MergeTagsJob(), new Privilege(Privilege::MergeTags));
		$this->testRegularPrivilege(new RenameTagsJob(), new Privilege(Privilege::RenameTags));
	}

	protected function testRegularPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());
	}

	public function testDynamicPostPrivileges()
	{
		$this->login($this->mockUser());

		$this->testDynamicPostPrivilege(new DeletePostJob(), new Privilege(Privilege::DeletePost));
		$this->testDynamicPostPrivilege(new EditPostContentJob(), new Privilege(Privilege::EditPostContent));
		$this->testDynamicPostPrivilege(new EditPostRelationsJob(), new Privilege(Privilege::EditPostRelations));
		$this->testDynamicPostPrivilege(new EditPostSafetyJob(), new Privilege(Privilege::EditPostSafety));
		$this->testDynamicPostPrivilege(new EditPostSourceJob(), new Privilege(Privilege::EditPostSource));
		$this->testDynamicPostPrivilege(new EditPostTagsJob(), new Privilege(Privilege::EditPostTags));
		$this->testDynamicPostPrivilege(new EditPostThumbJob(), new Privilege(Privilege::EditPostThumb));
		$this->testDynamicPostPrivilege(new FeaturePostJob(), new Privilege(Privilege::FeaturePost));
		$this->testDynamicPostPrivilege(new FlagPostJob(), new Privilege(Privilege::FlagPost));
		$this->testDynamicPostPrivilege(new ScorePostJob(), new Privilege(Privilege::ScorePost));
		$this->testDynamicPostPrivilege(new TogglePostTagJob(), new Privilege(Privilege::EditPostTags));
		$this->testDynamicPostPrivilege(new TogglePostVisibilityJob(), new Privilege(Privilege::HidePost));
		$this->testDynamicPostPrivilege(new TogglePostFavoriteJob(), new Privilege(Privilege::FavoritePost));
	}

	protected function testDynamicPostPrivilege($job, $expectedPrivilege)
	{
		$this->testedJobs []= $job;

		$ownPost = $this->mockPost(Auth::getCurrentUser());
		$otherPost = $this->mockPost($this->mockUser());

		$expectedPrivilege->secondary = 'all';
		$job->setArgument(AbstractJob::POST_ID, $otherPost->getId());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());

		$expectedPrivilege->secondary = 'own';
		$job->setArgument(AbstractJob::POST_ID, $ownPost->getId());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());
	}

	public function testDynamicPostRetrievalPrivileges()
	{
		$jobs =
		[
			new GetPostJob(),
			new GetPostContentJob(),
		];

		$post = $this->mockPost($this->mockUser());

		foreach ($jobs as $job)
		{
			$this->testedJobs []= $job;

			$post->setHidden(true);
			PostModel::save($post);

			$job->setArgument(AbstractJob::POST_ID, $post->getId());
			$job->setArgument(AbstractJob::POST_NAME, $post->getName());
			$job->prepare();
			$this->assert->areEquivalent([
				new Privilege(Privilege::ViewPost, 'hidden'),
				new Privilege(Privilege::ViewPost, 'safe')], $job->requiresPrivilege());
		}
	}

	public function testDynamicPostThumbPrivileges()
	{
		$job = new GetPostThumbJob();
		$this->testedJobs []= $job;
		$this->assert->areEquivalent(false, $job->requiresPrivilege());
	}

	public function testDynamicUserPrivileges()
	{
		$ownUser = $this->mockUser();
		$this->login($ownUser);

		$this->testDynamicUserPrivilege(new DeleteUserJob(), new Privilege(Privilege::DeleteUser));
		$this->testDynamicUserPrivilege(new EditUserAccessRankJob(), new Privilege(Privilege::ChangeUserAccessRank));
		$this->testDynamicUserPrivilege(new EditUserEmailJob(), new Privilege(Privilege::ChangeUserEmail));
		$this->testDynamicUserPrivilege(new EditUserNameJob(), new Privilege(Privilege::ChangeUserName));
		$this->testDynamicUserPrivilege(new EditUserPasswordJob(), new Privilege(Privilege::ChangeUserPassword));
		$this->testDynamicUserPrivilege(new FlagUserJob(), new Privilege(Privilege::FlagUser));
		$this->testDynamicUserPrivilege(new GetUserJob(), new Privilege(Privilege::ViewUser));
		$this->testDynamicUserPrivilege(new ToggleUserBanJob(), new Privilege(Privilege::BanUser));
	}

	protected function testDynamicUserPrivilege($job, $expectedPrivilege)
	{
		$ownUser = Auth::getCurrentUser();

		$otherUser = $this->mockUser($this->mockUser());
		$otherUser->setName('somebody-else');
		UserModel::save($otherUser);

		$this->testedJobs []= $job;

		$expectedPrivilege->secondary = 'own';
		$job->setArgument(AbstractJob::USER_NAME, $ownUser->getName());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());

		$expectedPrivilege->secondary = 'all';
		$job->setArgument(AbstractJob::USER_NAME, $otherUser->getName());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());
	}

	public function testDynamicCommentPrivileges()
	{
		$this->login($this->mockUser());

		$this->testDynamicCommentPrivilege(new DeleteCommentJob(), new Privilege(Privilege::DeleteComment));
		$this->testDynamicCommentPrivilege(new EditCommentJob(), new Privilege(Privilege::EditComment));
	}

	protected function testDynamicCommentPrivilege($job, $expectedPrivilege)
	{
		$ownComment = $this->mockComment(Auth::getCurrentUser());
		$otherComment = $this->mockComment($this->mockUser());

		$this->testedJobs []= $job;

		$expectedPrivilege->secondary = 'own';
		$job->setArgument(AbstractJob::COMMENT_ID, $ownComment->getId());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());

		$expectedPrivilege->secondary = 'all';
		$job->setArgument(AbstractJob::COMMENT_ID, $otherComment->getId());
		$job->prepare();
		$this->assert->areEquivalent($expectedPrivilege, $job->requiresPrivilege());
	}

	public function testPrivilegeEnforcing()
	{
		$this->assert->throws(function()
		{
			$post = $this->mockPost(Auth::getCurrentUser());
			getConfig()->registration->needEmailForCommenting = false;
			return Api::run(
				new AddCommentJob(),
				[
					AddCommentJob::POST_ID => $post->getId(),
					AddCommentJob::TEXT => 'alohaaa',
				]);
		}, 'Insufficient privileges');
	}
}
