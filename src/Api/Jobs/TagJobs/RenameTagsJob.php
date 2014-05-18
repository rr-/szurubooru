<?php
class RenameTagsJob extends AbstractJob
{
	public function execute()
	{
		$sourceTag = $this->getArgument(JobArgs::ARG_SOURCE_TAG_NAME);
		$targetTag = $this->getArgument(JobArgs::ARG_TARGET_TAG_NAME);

		TagModel::removeUnused();
		TagModel::rename($sourceTag, $targetTag);

		Logger::log('{user} renamed {source} to {target}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'source' => TextHelper::reprTag($sourceTag),
			'target' => TextHelper::reprTag($targetTag)]);
	}

	public function getRequiredArguments()
	{
		return JobArgs::Conjunction(
			JobArgs::ARG_SOURCE_TAG_NAME,
			JobArgs::ARG_TARGET_TAG_NAME);
	}

	public function getRequiredMainPrivilege()
	{
		return Privilege::RenameTags;
	}

	public function getRequiredSubPrivileges()
	{
		return null;
	}

	public function isAuthenticationRequired()
	{
		return false;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
