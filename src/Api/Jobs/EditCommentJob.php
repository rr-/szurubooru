<?php
class EditCommentJob extends AbstractJob
{
	protected $comment;

	public function prepare()
	{
		$this->comment = CommentModel::findById($this->getArgument(self::COMMENT_ID));
	}

	public function execute()
	{
		$comment = $this->comment;

		$comment->setCreationTime(time());
		$comment->setText($this->getArgument(self::TEXT));

		CommentModel::save($comment);
		Logger::log('{user} edited comment in {post}', [
			'user' => TextHelper::reprUser(Auth::getCurrentUser()),
			'post' => TextHelper::reprPost($comment->getPost())]);

		return $comment;
	}

	public function requiresPrivilege()
	{
		return new Privilege(
			Privilege::EditComment,
			Access::getIdentity($this->comment->getCommenter()));
	}

	public function requiresAuthentication()
	{
		return true;
	}

	public function requiresConfirmedEmail()
	{
		return false;
	}
}
