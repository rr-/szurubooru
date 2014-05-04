<?php
class RenameTagsJob extends AbstractJob
{
	const SOURCE_TAG_NAME = 'source-tag-name';
	const TARGET_TAG_NAME = 'target-tag-name';

	public function execute()
	{
		$sourceTag = $this->getArgument(self::SOURCE_TAG_NAME);
		$targetTag = $this->getArgument(self::TARGET_TAG_NAME);

		TagModel::removeUnused();
		TagModel::rename($sourceTag, $targetTag);

		LogHelper::log('{user} renamed {source} to {target}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'source' => TextHelper::reprTag($sourceTag),
			'target' => TextHelper::reprTag($targetTag)]);
	}

	public function requiresPrivilege()
	{
		return Privilege::RenameTags;
	}

	public function requiresAuthentication()
	{
		return false;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
