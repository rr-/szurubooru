<?php
namespace Szurubooru;

class Privilege
{
    const REGISTER = 'register';
    const LIST_USERS = 'listUsers';
    const VIEW_USERS = 'viewUsers';
    const VIEW_ALL_EMAIL_ADDRESSES = 'viewAllEmailAddresses';
    const VIEW_ALL_ACCESS_RANKS = 'viewAllAccessRanks';
    const CHANGE_ACCESS_RANK = 'changeAccessRank';
    const CHANGE_OWN_AVATAR_STYLE = 'changeOwnAvatarStyle';
    const CHANGE_OWN_EMAIL_ADDRESS = 'changeOwnEmailAddress';
    const CHANGE_OWN_NAME = 'changeOwnName';
    const CHANGE_OWN_PASSWORD = 'changeOwnPassword';
    const CHANGE_ALL_AVATAR_STYLES = 'changeAllAvatarStyles';
    const CHANGE_ALL_EMAIL_ADDRESSES = 'changeAllEmailAddresses';
    const CHANGE_ALL_NAMES = 'changeAllNames';
    const CHANGE_ALL_PASSWORDS = 'changeAllPasswords';
    const DELETE_OWN_ACCOUNT = 'deleteOwnAccount';
    const DELETE_ALL_ACCOUNTS = 'deleteAllAccounts';
    const BAN_USERS = 'banUsers';

    const LIST_POSTS = 'listPosts';
    const VIEW_POSTS = 'viewPosts';
    const UPLOAD_POSTS = 'uploadPosts';
    const UPLOAD_POSTS_ANONYMOUSLY = 'uploadPostsAnonymously';
    const DELETE_POSTS = 'deletePosts';
    const FEATURE_POSTS = 'featurePosts';
    const CHANGE_POST_SAFETY = 'changePostSafety';
    const CHANGE_POST_SOURCE = 'changePostSource';
    const CHANGE_POST_TAGS = 'changePostTags';
    const CHANGE_POST_CONTENT = 'changePostContent';
    const CHANGE_POST_THUMBNAIL = 'changePostThumbnail';
    const CHANGE_POST_RELATIONS = 'changePostRelations';
    const CHANGE_POST_FLAGS = 'changePostFlags';

    const ADD_POST_NOTES = 'addPostNotes';
    const EDIT_POST_NOTES = 'editPostNotes';
    const DELETE_POST_NOTES = 'deletePostNotes';

    const LIST_TAGS = 'listTags';
    const MASS_TAG = 'massTag';
    const CHANGE_TAG_NAME = 'changeTagName';
    const CHANGE_TAG_CATEGORY = 'changeTagCategory';
    const CHANGE_TAG_IMPLICATIONS = 'changeTagImplications';
    const CHANGE_TAG_SUGGESTIONS = 'changeTagSuggestions';
    const BAN_TAGS = 'banTags';
    const DELETE_TAGS = 'deleteTags';
    const MERGE_TAGS = 'mergeTags';

    const LIST_COMMENTS = 'listComments';
    const ADD_COMMENTS = 'addComments';
    const EDIT_OWN_COMMENTS = 'editOwnComments';
    const EDIT_ALL_COMMENTS = 'editAllComments';
    const DELETE_OWN_COMMENTS = 'deleteOwnComments';
    const DELETE_ALL_COMMENTS = 'deleteAllComments';

    const VIEW_HISTORY = 'viewHistory';
}
