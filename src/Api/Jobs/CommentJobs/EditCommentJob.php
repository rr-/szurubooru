<?php
class EditCommentJob extends AbstractCommentJob
{
	public function execute()
	{
		$comment = $this->comment;

		$comment->setCreationTime(time());
		$comment->setText($this->getArgument(JobArgs::ARG_NEW_TEXT));

		CommentModel::save($comment);
		Logger::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function getRequiredSubArguments()
	{
		return JobArgs::ARG_NEW_TEXT;
	}

	public function getRequiredPrivileges()
	{
		return new Privilege(
			Privilege::EditComment,
			Access::getIdentity($this->comment->getCommenter()));
	}

	public function isAuthenticationRequired()
	{
		return true;
	}

	public function isConfirmedEmailRequired()
	{
		return false;
	}
}
