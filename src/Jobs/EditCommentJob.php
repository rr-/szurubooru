<?php
class EditCommentJob extends AbstractJob
{
	protected $comment;

	public function prepare()
	{
		$this->comment = CommentModel::findById($this->getArgument(JobArgs::COMMENT_ID));
	}

	public function execute()
	{
		$user = Auth::getCurrentUser();
		$comment = $this->comment;

		$comment->commentDate = time();
		$comment->text = CommentModel::validateText($this->getArgument(JobArgs::TEXT));

		CommentModel::save($comment);
		LogHelper::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser($user),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return
		[
			Privilege::EditComment,
			Access::getIdentity($this->comment->getCommenter())
		];
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return getConfig()->registration->needEmailForCommenting;
	}
}
