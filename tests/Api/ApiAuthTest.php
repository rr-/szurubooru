<?php
class ApiAuthTest extends AbstractFullApiTest
{
	public function testAllAuth()
	{
		$this->testAuth(new AcceptUserRegistrationJob(), false);
		$this->testAuth(new ActivateUserEmailJob(), false);
		$this->testAuth(new AddPostJob(), false);
		$this->testAuth(new AddCommentJob(), false);
		$this->testAuth(new AddUserJob(), false);
		$this->testAuth(new DeletePostJob(), true);
		$this->testAuth(new DeleteCommentJob(), true);
		$this->testAuth(new DeleteUserJob(), false);
		$this->testAuth(new EditCommentJob(), true);
		$this->testAuth(new EditPostContentJob(), false);
		$this->testAuth(new EditPostJob(), false);
		$this->testAuth(new EditPostRelationsJob(), false);
		$this->testAuth(new EditPostSafetyJob(), false);
		$this->testAuth(new EditPostSourceJob(), false);
		$this->testAuth(new EditPostTagsJob(), false);
		$this->testAuth(new EditPostThumbJob(), false);
		$this->testAuth(new EditUserAccessRankJob(), false);
		$this->testAuth(new EditUserEmailJob(), false);
		$this->testAuth(new EditUserJob(), false);
		$this->testAuth(new EditUserNameJob(), false);
		$this->testAuth(new EditUserPasswordJob(), false);
		$this->testAuth(new FeaturePostJob(), true);
		$this->testAuth(new FlagPostJob(), false);
		$this->testAuth(new FlagUserJob(), false);
		$this->testAuth(new GetLogJob(), false);
		$this->testAuth(new GetPostContentJob(), false);
		$this->testAuth(new GetPostJob(), false);
		$this->testAuth(new GetPostThumbJob(), false);
		$this->testAuth(new GetUserJob(), false);
		$this->testAuth(new ListCommentsJob(), false);
		$this->testAuth(new ListLogsJob(), false);
		$this->testAuth(new ListPostsJob(), false);
		$this->testAuth(new ListRelatedTagsJob(), false);
		$this->testAuth(new ListTagsJob(), false);
		$this->testAuth(new ListUsersJob(), false);
		$this->testAuth(new MergeTagsJob(), false);
		$this->testAuth(new PasswordResetJob(), false);
		$this->testAuth(new PreviewCommentJob(), false);
		$this->testAuth(new RenameTagsJob(), false);
		$this->testAuth(new ScorePostJob(), true);
		$this->testAuth(new TogglePostFavoriteJob(), true);
		$this->testAuth(new TogglePostTagJob(), false);
		$this->testAuth(new TogglePostVisibilityJob(), false);
		$this->testAuth(new ToggleUserBanJob(), false);
	}

	protected function testAuth($job, $expectedAuth)
	{
		$this->testedJobs []= $job;
		$this->assert->areEqual($expectedAuth, $job->requiresAuthentication());
	}

	public function testAuthEnforcing()
	{
		getConfig()->registration->needEmailForCommenting = false;
		$this->grantAccess('addComment');

		$comment = $this->mockComment(Auth::getCurrentUser());

		$this->assert->throws(function() use ($comment)
		{
			return Api::run(
				new DeleteCommentJob(),
				[
					DeleteCommentJob::COMMENT_ID => $comment->getId(),
				]);
		}, 'Not logged in');
	}
}
