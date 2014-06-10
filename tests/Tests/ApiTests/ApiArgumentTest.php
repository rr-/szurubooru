<?php
class ApiArgumentTest extends AbstractFullApiTest
{
	public function testAcceptUserRegistrationJob()
	{
		$this->testArguments(new AcceptUserRegistrationJob(),
			$this->getUserSelector());
	}

	public function testActivateUserEmailJob()
	{
		$this->testArguments(new ActivateUserEmailJob(),
			JobArgs::Alternative(
				JobArgs::ARG_TOKEN,
				$this->getUserSelector()));
	}

	public function testAddPostJob()
	{
		$this->testArguments(new AddPostJob(),
			JobArgs::Optional(
				JobArgs::ARG_ANONYMOUS));
	}

	public function testAddCommentJob()
	{
		$this->testArguments(new AddCommentJob(),
			JobArgs::Conjunction(
				$this->getPostSelector(),
				JobArgs::ARG_NEW_TEXT));
	}

	public function testAddUserJob()
	{
		$this->testArguments(new AddUserJob(), null);
	}

	public function testDeletePostJob()
	{
		$this->testArguments(new DeletePostJob(),
			$this->getPostSelector());
	}

	public function testDeleteCommentJob()
	{
		$this->testArguments(new DeleteCommentJob(),
			$this->getCommentSelector());
	}

	public function testDeleteUserJob()
	{
		$this->testArguments(new DeleteUserJob(),
			$this->getUserSelector());
	}

	public function testEditCommentJob()
	{
		$this->testArguments(new EditCommentJob(),
			JobArgs::Conjunction(
				JobArgs::ARG_NEW_TEXT,
				$this->getCommentSelector()));
	}

	public function testEditPostContentJob()
	{
		$this->testArguments(new EditPostContentJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::Alternative(
					JobArgs::ARG_NEW_POST_CONTENT,
					JobArgs::ARG_NEW_POST_CONTENT_URL)));
	}

	public function testEditPostJob()
	{
		$this->testArguments(new EditPostJob(),
			$this->getPostSelectorForEditing());
	}

	public function testEditPostRelationsJob()
	{
		$this->testArguments(new EditPostRelationsJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::ARG_NEW_RELATED_POST_IDS));
	}

	public function testEditPostSafetyJob()
	{
		$this->testArguments(new EditPostSafetyJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::ARG_NEW_SAFETY));
	}

	public function testEditPostSourceJob()
	{
		$this->testArguments(new EditPostSourceJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::ARG_NEW_SOURCE));
	}

	public function testEditPostTagsJob()
	{
		$this->testArguments(new EditPostTagsJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::ARG_NEW_TAG_NAMES));
	}

	public function testEditPostThumbnailJob()
	{
		$this->testArguments(new EditPostThumbnailJob(),
			JobArgs::Conjunction(
				$this->getPostSelectorForEditing(),
				JobArgs::ARG_NEW_THUMBNAIL_CONTENT));
	}

	public function testEditUserAccessRankJob()
	{
		$this->testArguments(new EditUserAccessRankJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_ACCESS_RANK));
	}

	public function testEditUserEmailJob()
	{
		$this->testArguments(new EditUserEmailJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_EMAIL));
	}

	public function testEditUserJob()
	{
		$this->testArguments(new EditUserJob(),
			$this->getUserSelector());
	}

	public function testEditUserNameJob()
	{
		$this->testArguments(new EditUserNameJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_USER_NAME));
	}

	public function testEditUserAvatarJob()
	{
		$this->testArguments(new EditUserAvatarJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_AVATAR_STYLE,
				JobArgs::Optional(JobArgs::ARG_NEW_AVATAR_CONTENT)));
	}

	public function testEditUserPasswordJob()
	{
		$this->testArguments(new EditUserPasswordJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_PASSWORD));
	}

	public function testFeaturePostJob()
	{
		$this->testArguments(new FeaturePostJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_ANONYMOUS),
				$this->getPostSelector()));
	}

	public function testFlagPostJob()
	{
		$this->testArguments(new FlagPostJob(),
			$this->getPostSelector());
	}

	public function testFlagUserJob()
	{
		$this->testArguments(new FlagUserJob(),
			$this->getUserSelector());
	}

	public function testGetLogJob()
	{
		$this->testArguments(new GetLogJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_QUERY),
				JobArgs::ARG_LOG_ID,
				JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER)));
	}

	public function testGetPropertyJob()
	{
		$this->testArguments(new GetPropertyJob(), JobArgs::ARG_QUERY);
	}

	public function testGetPostContentJob()
	{
		$this->testArguments(new GetPostContentJob(),
			$this->getPostSafeSelector());
	}

	public function testGetPostJob()
	{
		$this->testArguments(new GetPostJob(),
			$this->getPostSelector());
	}

	public function testGetPostThumbnailJob()
	{
		$this->testArguments(new GetPostThumbnailJob(), $this->getPostSafeSelector());
	}

	public function testGetUserJob()
	{
		$this->testArguments(new GetUserJob(),
			$this->getUserSelector());
	}

	public function testGetUserSettingsJob()
	{
		$this->testArguments(new GetUserSettingsJob(),
			$this->getUserSelector());
	}

	public function testListCommentsJob()
	{
		$this->testArguments(new ListCommentsJob(),
			JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER));
	}

	public function testListLogsJob()
	{
		$this->testArguments(new ListLogsJob(),
			null);
	}

	public function testListPostsJob()
	{
		$this->testArguments(new ListPostsJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_QUERY),
				JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER)));
	}

	public function testListRelatedTagsJob()
	{
		$this->testArguments(new ListRelatedTagsJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_TAG_NAMES),
				JobArgs::ARG_TAG_NAME,
				JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER)));
	}

	public function testListTagsJob()
	{
		$this->testArguments(new ListTagsJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_QUERY),
				JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER)));
	}

	public function testListUsersJob()
	{
		$this->testArguments(new ListUsersJob(),
			JobArgs::Conjunction(
				JobArgs::Optional(JobArgs::ARG_QUERY),
				JobArgs::Optional(JobArgs::ARG_PAGE_NUMBER)));
	}

	public function testMergeTagsJob()
	{
		$this->testArguments(new MergeTagsJob(),
			JobArgs::Conjunction(
				JobArgs::ARG_SOURCE_TAG_NAME,
				JobArgs::ARG_TARGET_TAG_NAME));
	}

	public function testRenameTagsJob()
	{
		$this->testArguments(new RenameTagsJob(),
			JobArgs::Conjunction(
				JobArgs::ARG_SOURCE_TAG_NAME,
				JobArgs::ARG_TARGET_TAG_NAME));
	}

	public function testPasswordResetJob()
	{
		$this->testArguments(new PasswordResetJob(),
			JobArgs::Alternative(
				JobArgs::ARG_TOKEN,
				$this->getUserSelector()));
	}

	public function testEditUserSettingsJob()
	{
		$this->testArguments(new EditUserSettingsJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_SETTINGS));
	}

	public function testPreviewCommentJob()
	{
		$this->testArguments(new PreviewCommentJob(),
			JobArgs::Conjunction(
				JobArgs::Alternative(
					$this->getPostSelector(),
					$this->getCommentSelector()),
				JobArgs::ARG_NEW_TEXT));
	}

	public function testScorePostJob()
	{
		$this->testArguments(new ScorePostJob(),
			JobArgs::Conjunction(
				$this->getPostSelector(),
				JobArgs::ARG_NEW_POST_SCORE));
	}

	public function testTogglePostFavoriteJob()
	{
		$this->testArguments(new TogglePostFavoriteJob(),
			JobArgs::Conjunction(
				$this->getPostSelector(),
				JobArgs::ARG_NEW_STATE));
	}

	public function testTogglePostTagJob()
	{
		$this->testArguments(new TogglePostTagJob(),
			JobArgs::Conjunction(
				$this->getPostSelector(),
				JobArgs::ARG_TAG_NAME,
				JobArgs::ARG_NEW_STATE));
	}

	public function testTogglePostVisibilityJob()
	{
		$this->testArguments(new TogglePostVisibilityJob(),
			JobArgs::Conjunction(
				$this->getPostSelector(),
				JobArgs::ARG_NEW_STATE));
	}

	public function testToggleUserBanJob()
	{
		$this->testArguments(new ToggleUserBanJob(),
			JobArgs::Conjunction(
				$this->getUserSelector(),
				JobArgs::ARG_NEW_STATE));
	}

	protected function testArguments($job, $arguments)
	{
		$this->testedJobs []= $job;
		$this->assert->areEquivalent($arguments, $job->getRequiredArguments());
	}

	protected function getPostSelector()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_POST_ENTITY,
			JobArgs::ARG_POST_ID,
			JobArgs::ARG_POST_NAME);
	}

	protected function getPostSelectorForEditing()
	{
		return JobArgs::Conjunction(
			JobArgs::ARG_POST_REVISION,
			$this->getPostSelector());
	}

	protected function getPostSafeSelector()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_POST_NAME,
			JobArgs::ARG_POST_ENTITY);
	}

	protected function getCommentSelector()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_COMMENT_ENTITY,
			JobArgs::ARG_COMMENT_ID);
	}

	protected function getUserSelector()
	{
		return JobArgs::Alternative(
			JobArgs::ARG_USER_EMAIL,
			JobArgs::ARG_USER_ENTITY,
			JobArgs::ARG_USER_NAME);
	}
}
