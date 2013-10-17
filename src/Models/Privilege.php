<?php
class Privilege extends Enum
{
	const ListPosts = 1;
	const UploadPost = 2;
	const ViewPost = 3;
	const RetrievePost = 4;
	const FavoritePost = 5;
	const EditPostSafety = 6;
	const EditPostTags = 7;
	const EditPostThumb = 8;
	const HidePost = 9;
	const DeletePost = 10;

	const ListUsers = 11;
	const ViewUser = 12;
	const ViewUserEmail = 22;
	const BanUser = 13;
	const AcceptUserRegistration = 14;
	const ChangeUserPassword = 15;
	const ChangeUserAccessRank = 16;
	const ChangeUserEmail = 17;
	const ChangeUserName = 18;
	const DeleteUser = 19;

	const ListComments = 20;
	const AddComment = 23;
	const DeleteComment = 24;

	const ListTags = 21;
}
