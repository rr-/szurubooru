<?php
class ApiEmailRequirementsTest extends AbstractFullApiTest
{
	public function testRegularEmailRequirements()
	{
		getConfig()->registration->needEmailForCommenting = true;
		getConfig()->registration->needEmailForUploading = true;

		$this->testRegularEmailRequirement(new AcceptUserRegistrationJob());
		$this->testRegularEmailRequirement(new ActivateUserEmailJob());
		$this->testRegularEmailRequirement(new AddUserJob());
		$this->testRegularEmailRequirement(new DeletePostJob());
		$this->testRegularEmailRequirement(new DeleteCommentJob());
		$this->testRegularEmailRequirement(new DeleteUserJob());
		$this->testRegularEmailRequirement(new EditCommentJob());
		$this->testRegularEmailRequirement(new EditPostContentJob());
		$this->testRegularEmailRequirement(new EditPostJob());
		$this->testRegularEmailRequirement(new EditPostRelationsJob());
		$this->testRegularEmailRequirement(new EditPostSafetyJob());
		$this->testRegularEmailRequirement(new EditPostSourceJob());
		$this->testRegularEmailRequirement(new EditPostTagsJob());
		$this->testRegularEmailRequirement(new EditPostThumbJob());
		$this->testRegularEmailRequirement(new EditUserAccessRankJob());
		$this->testRegularEmailRequirement(new EditUserEmailJob());
		$this->testRegularEmailRequirement(new EditUserJob());
		$this->testRegularEmailRequirement(new EditUserNameJob());
		$this->testRegularEmailRequirement(new EditUserPasswordJob());
		$this->testRegularEmailRequirement(new FeaturePostJob());
		$this->testRegularEmailRequirement(new FlagPostJob());
		$this->testRegularEmailRequirement(new FlagUserJob());
		$this->testRegularEmailRequirement(new GetLogJob());
		$this->testRegularEmailRequirement(new GetPostContentJob());
		$this->testRegularEmailRequirement(new GetPostJob());
		$this->testRegularEmailRequirement(new GetPostThumbJob());
		$this->testRegularEmailRequirement(new GetUserJob());
		$this->testRegularEmailRequirement(new ListCommentsJob());
		$this->testRegularEmailRequirement(new ListLogsJob());
		$this->testRegularEmailRequirement(new ListPostsJob());
		$this->testRegularEmailRequirement(new ListRelatedTagsJob());
		$this->testRegularEmailRequirement(new ListTagsJob());
		$this->testRegularEmailRequirement(new ListUsersJob());
		$this->testRegularEmailRequirement(new MergeTagsJob());
		$this->testRegularEmailRequirement(new PasswordResetJob());
		$this->testRegularEmailRequirement(new RenameTagsJob());
		$this->testRegularEmailRequirement(new ScorePostJob());
		$this->testRegularEmailRequirement(new TogglePostFavoriteJob());
		$this->testRegularEmailRequirement(new TogglePostTagJob());
		$this->testRegularEmailRequirement(new TogglePostVisibilityJob());
		$this->testRegularEmailRequirement(new ToggleUserBanJob());
	}

	protected function testRegularEmailRequirement($job)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual(false, $job->requiresConfirmedEmail());
	}

	public function testCommentsEmailRequirements()
	{
		$this->testCommentEmailRequirement(new AddCommentJob());
		$this->testCommentEmailRequirement(new PreviewCommentJob());
	}

	protected function testCommentEmailRequirement($job)
	{
		$this->testedJobs []= $job;

		getConfig()->registration->needEmailForCommenting = false;
		$this->assert->areEqual(false, $job->requiresConfirmedEmail());

		getConfig()->registration->needEmailForCommenting = true;
		$this->assert->areEqual(true, $job->requiresConfirmedEmail());
	}

	public function testPostingEmailRequirement()
	{
		$job = new AddPostJob();

		$this->testedJobs []= $job;

		getConfig()->registration->needEmailForUploading = false;
		$this->assert->areEqual(false, $job->requiresConfirmedEmail());

		getConfig()->registration->needEmailForUploading = true;
		$this->assert->areEqual(true, $job->requiresConfirmedEmail());
	}

	public function testEnforcing()
	{
		$this->grantAccess('addComment');
		$this->login($this->mockUser());
		getConfig()->registration->needEmailForCommenting = true;
		$this->assert->throws(function()
		{
			$post = $this->mockPost(Auth::getCurrentUser());

			return Api::run(
				new AddCommentJob(),
				[
					JobArgs::ARG_POST_ID => $post->getId(),
					JobArgs::ARG_NEW_TEXT => 'alohaaa',
				]);
		}, 'Need e-mail');
	}
}
