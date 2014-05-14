<?php
class JobArgs
{
	const ARG_ANONYMOUS = 'anonymous';

	const ARG_PAGE_NUMBER = 'page-number';
	const ARG_QUERY = 'query';
	const ARG_TOKEN = 'token';

	const ARG_USER_ENTITY = 'user';
	#const ARG_USER_ID = 'user-id';
	const ARG_USER_NAME = 'user-name';
	const ARG_USER_EMAIL = 'user-email';

	const ARG_POST_ENTITY = 'post';
	const ARG_POST_ID = 'post-id';
	const ARG_POST_NAME = 'post-name';

	const ARG_TAG_NAME = 'tag-name';
	const ARG_TAG_NAMES = 'tag-names';

	const ARG_COMMENT_ENTITY = 'comment';
	const ARG_COMMENT_ID = 'comment-id';

	const ARG_LOG_ID = 'log-id';

	const ARG_THUMB_WIDTH = 'thumb-width';
	const ARG_THUMB_HEIGHT = 'thumb-height';

	const ARG_NEW_TEXT = 'new-text';
	const ARG_NEW_STATE = 'new-state';

	const ARG_NEW_POST_CONTENT = 'new-post-content';
	const ARG_NEW_POST_CONTENT_URL = 'new-post-content-url';
	const ARG_NEW_RELATED_POST_IDS = 'new-related-post-ids';
	const ARG_NEW_SAFETY = 'new-safety';
	const ARG_NEW_SOURCE = 'new-source';
	const ARG_NEW_THUMB_CONTENT = 'new-thumb-content';
	const ARG_NEW_TAG_NAMES = 'new-tag-names';

	const ARG_NEW_ACCESS_RANK = 'new-access-rank';
	const ARG_NEW_EMAIL = 'new-email';
	const ARG_NEW_USER_NAME = 'new-user-name';
	const ARG_NEW_PASSWORD = 'new-password';
	const ARG_NEW_SETTINGS = 'new-settings';

	const ARG_NEW_POST_SCORE = 'new-post-score';
	const ARG_SOURCE_TAG_NAME = 'source-tag-name';
	const ARG_TARGET_TAG_NAME = 'target-tag-name';

	public static function Alternative()
	{
		return JobArgsAlternative::factory(func_get_args());
	}

	public static function Conjunction()
	{
		return JobArgsConjunction::factory(func_get_args());
	}

	public static function Optional()
	{
		return JobArgsOptional::factory(func_get_args());
	}
}
