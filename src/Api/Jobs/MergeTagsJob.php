<?php
class MergeTagsJob extends AbstractJob
{
	const SOURCE_TAG_NAME = 'source-tag-name';
	const TARGET_TAG_NAME = 'target-tag-name';

	public function execute()
	{
		$sourceTag = $this->getArgument(self::SOURCE_TAG_NAME);
		$targetTag = $this->getArgument(self::TARGET_TAG_NAME);

		TagModel::removeUnused();
		TagModel::merge($sourceTag, $targetTag);

		LogHelper::log('{user} merged {source} with {target}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'source' => TextHelper::reprTag($sourceTag),
			'target' => TextHelper::reprTag($targetTag)]);
	}

	public function requiresPrivilege()
	{
		return new Privilege(Privilege::MergeTags);
	}
}
