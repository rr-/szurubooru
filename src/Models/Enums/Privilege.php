<?php
class Privilege extends Enum
{
	const ListPosts = 1;
	const ViewPost = 3;
	const RetrievePost = 4;
	const FavoritePost = 5;
	const HidePost = 9;
	const DeletePost = 10;
	const FeaturePost = 25;
	const ScorePost = 31;
	const FlagPost = 34;

	const EditPost = 45;
	const EditPostSafety = 6;
	const EditPostTags = 7;
	const EditPostThumb = 8;
	const EditPostSource = 26;
	const EditPostRelations = 30;
	const EditPostContent = 36;

	const AddPost = 2;
	const AddPostSafety = 39;
	const AddPostTags = 40;
	const AddPostThumb = 41;
	const AddPostSource = 42;
	const AddPostRelations = 43;
	const AddPostContent = 44;

	const RegisterAccount = 38;
	const ListUsers = 11;
	const ViewUser = 12;
	const ViewUserEmail = 22;
	const BanUser = 13;
	const AcceptUserRegistration = 14;
	const ChangeUserPassword = 15;
	const ChangeUserAccessRank = 16;
	const ChangeUserEmail = 17;
	const ChangeUserName = 18;
	const ChangeUserSettings = 28;
	const DeleteUser = 19;
	const FlagUser = 35;

	const ListComments = 20;
	const AddComment = 23;
	const DeleteComment = 24;
	const EditComment = 37;

	const ListTags = 21;
	const MergeTags = 27;
	const RenameTags = 27;
	const MassTag = 29;

	const ListLogs = 32;
	const ViewLog = 33;

	public $primary;
	public $secondary;

	public function __construct($primary, $secondary = null)
	{
		$this->primary = $primary;
		$this->secondary = strtolower($secondary);
	}

	public function toString()
	{
		$string = self::_toString($this->primary);
		if ($this->secondary)
			$string .= '.' . $this->secondary;
		return $string;
	}
}
