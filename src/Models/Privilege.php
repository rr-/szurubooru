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
	const ListComments = 12;
	const ListTags = 13;
}
