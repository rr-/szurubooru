<?php
class Privilege extends AbstractEnum implements IEnum
{
	const ListPosts = 'listPosts';
	const ViewPost = 'viewPost';
	const RetrievePost = 'retrievePost';
	const FavoritePost = 'favoritePost';
	const HidePost = 'hidePost';
	const DeletePost = 'deletePost';
	const FeaturePost = 'featurePost';
	const ScorePost = 'scorePost';
	const FlagPost = 'flagPost';

	const EditPost = 'editPost';
	const EditPostSafety = 'editPostSafety';
	const EditPostTags = 'editPostTags';
	const EditPostThumbnail = 'editPostThumbnail';
	const EditPostSource = 'editPostSource';
	const EditPostRelations = 'editPostRelations';
	const EditPostContent = 'editPostContent';

	const AddPost = 'addPost';
	const AddPostSafety = 'addPostSafety';
	const AddPostTags = 'addPostTags';
	const AddPostThumbnail = 'addPostThumbnail';
	const AddPostSource = 'addPostSource';
	const AddPostRelations = 'addPostRelations';
	const AddPostContent = 'addPostContent';

	const RegisterAccount = 'registerAccount';
	const ListUsers = 'listUsers';
	const ViewUser = 'viewUser';
	const ViewUserEmail = 'viewUserEmail';
	const BanUser = 'banUser';
	const AcceptUserRegistration = 'acceptUserRegistration';
	const EditUserPassword = 'editUserPassword';
	const EditUserAccessRank = 'editUserAccessRank';
	const EditUserEmail = 'editUserEmail';
	const EditUserEmailNoConfirm = 'editUserEmailNoConfirm';
	const EditUserName = 'editUserName';
	const EditUserSettings = 'editUserSettings';
	const DeleteUser = 'deleteUser';
	const FlagUser = 'flagUser';

	const ListComments = 'listComments';
	const AddComment = 'addComment';
	const DeleteComment = 'deleteComment';
	const EditComment = 'editComment';

	const ListTags = 'listTags';
	const MergeTags = 'mergeTags';
	const RenameTags = 'renameTags';
	const MassTag = 'massTag';

	const ListLogs = 'listLogs';
	const ViewLog = 'viewLog';

	public $primary;
	public $secondary;

	public function __construct($primary, $secondary = null)
	{
		$this->primary = $primary;
		$this->secondary = strtolower($secondary);
	}

	public function toString()
	{
		$string = $this->primary;
		if ($this->secondary)
			$string .= '.' . $this->secondary;
		return $string;
	}

	public function toDisplayString()
	{
		return $this->toString();
	}
}
