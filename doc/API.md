`szurubooru` uses REST API for all operations.



# Table of contents

1. [General rules](#general-rules)

   - [Authentication](#authentication)
   - [User token authentication](#user-token-authentication)
   - [Basic requests](#basic-requests)
   - [File uploads](#file-uploads)
   - [Error handling](#error-handling)
   - [Field selecting](#field-selecting)
   - [Versioning](#versioning)
   - [Webhooks](#webhooks)

2. [API reference](#api-reference)

    - Tag categories
        - [Listing tag categories](#listing-tag-categories)
        - [Creating tag category](#creating-tag-category)
        - [Updating tag category](#updating-tag-category)
        - [Getting tag category](#getting-tag-category)
        - [Deleting tag category](#deleting-tag-category)
        - [Setting default tag category](#setting-default-tag-category)
    - Tags
        - [Listing tags](#listing-tags)
        - [Creating tag](#creating-tag)
        - [Updating tag](#updating-tag)
        - [Getting tag](#getting-tag)
        - [Deleting tag](#deleting-tag)
        - [Merging tags](#merging-tags)
        - [Listing tag siblings](#listing-tag-siblings)
    - Posts
        - [Listing posts](#listing-posts)
        - [Creating post](#creating-post)
        - [Updating post](#updating-post)
        - [Getting post](#getting-post)
        - [Getting around post](#getting-around-post)
        - [Deleting post](#deleting-post)
        - [Merging posts](#merging-posts)
        - [Rating post](#rating-post)
        - [Adding post to favorites](#adding-post-to-favorites)
        - [Removing post from favorites](#removing-post-from-favorites)
        - [Getting featured post](#getting-featured-post)
        - [Featuring post](#featuring-post)
        - [Reverse image search](#reverse-image-search)
    - Pool categories
        - [Listing pool categories](#listing-pool-categories)
        - [Creating pool category](#creating-pool-category)
        - [Updating pool category](#updating-pool-category)
        - [Getting pool category](#getting-pool-category)
        - [Deleting pool category](#deleting-pool-category)
        - [Setting default pool category](#setting-default-pool-category)
    - Pools
        - [Listing pools](#listing-pool)
        - [Creating pool](#creating-pool)
        - [Updating pool](#updating-pool)
        - [Getting pool](#getting-pool)
        - [Deleting pool](#deleting-pool)
        - [Merging pools](#merging-pools)
    - Comments
        - [Listing comments](#listing-comments)
        - [Creating comment](#creating-comment)
        - [Updating comment](#updating-comment)
        - [Getting comment](#getting-comment)
        - [Deleting comment](#deleting-comment)
        - [Rating comment](#rating-comment)
    - Users
        - [Listing users](#listing-users)
        - [Creating user](#creating-user)
        - [Updating user](#updating-user)
        - [Getting user](#getting-user)
        - [Deleting user](#deleting-user)
    - User Tokens
        - [Listing user tokens](#listing-user-tokens)
        - [Creating user token](#creating-user-token)
        - [Updating user token](#updating-user-token)
        - [Deleting user token](#deleting-user-token)
    - Password reset
        - [Password reset - step 1: mail request](#password-reset---step-2-confirmation)
        - [Password reset - step 2: confirmation](#password-reset---step-2-confirmation)
    - Snapshots
        - [Listing snapshots](#listing-snapshots)
    - Global info
        - [Getting global info](#getting-global-info)
    - File uploads
        - [Uploading temporary file](#uploading-temporary-file)

3. [Resources](#resources)

   - [User](#user)
   - [Micro user](#micro-user)
   - [User token](#user-token)
   - [Tag category](#tag-category)
   - [Tag](#tag)
   - [Micro tag](#micro-tag)
   - [Post](#post)
   - [Micro post](#micro-post)
   - [Pool category](#pool-category)
   - [Pool](#pool)
   - [Micro pool](#micro-pool)
   - [Note](#note)
   - [Comment](#comment)
   - [Snapshot](#snapshot)
   - [Unpaged search result](#unpaged-search-result)
   - [Paged search result](#paged-search-result)
   - [Image search result](#image-search-result)

4. [Search](#search)



# General rules

## Authentication

Authentication is achieved by means of [basic HTTP
auth](https://en.wikipedia.org/wiki/Basic_access_authentication) or through the
use of [user token authentication](#user-token-authentication). For this
reason, it is recommended to connect through HTTPS. There are no sessions, so
every privileged request must be authenticated. Available privileges depend on
the user's rank. The way how rank translates to privileges is defined in the
server's configuration.

It is recommended to add `?bump-login` GET parameter to the first request in a
client "session" (where the definition of a session is up to the client), so
that the user's last login time is kept up to date.

## User token authentication

User token authentication works similarly to [basic HTTP
auth](https://en.wikipedia.org/wiki/Basic_access_authentication). Because it
operates similarly to ***basic HTTP auth*** it is still recommended to connect
through HTTPS. The authorization header uses the type of `Token` and the
username and token are encoded as Base64 and sent as the second parameter.

Example header for user1:token-is-more-secure
```
Authorization: Token dXNlcjE6dG9rZW4taXMtbW9yZS1zZWN1cmU=
```

The benefit of token authentication is that beyond the initial login to acquire
the first token, there is no need to transmit the user password in plaintext
via basic auth. Additionally tokens can be revoked at anytime allowing a
cleaner interface for isolating clients from user credentials.

## Basic requests

Every request must use `Content-Type: application/json` and `Accept:
application/json`. An exception to this rule are requests that upload files.

## File uploads

Requests that upload files must use `multipart/form-data` encoding. Any request
that bundles user files, must send the request data (which is JSON) as an
additional file with the special name of `metadata` (whereas the actual files
must have names specific to the API that is being used.)

Alternatively, the server can download the files from the Internet on client's
behalf. In that case, the request doesn't need to be specially encoded in any
way. The files, however, should be passed as regular fields appended with a
`Url` suffix. For example, to use `http://example.com/file.jpg` in an API that
accepts a file named `content`, the client should pass
`{"contentUrl":"http://example.com/file.jpg"}` as a part of the JSON message
body. When creating or updating post content using this method, the server can
also be configured to employ [youtube-dl](https://github.com/ytdl-org/youtube-dl)
to download content from popular sites such as youtube, gfycat, etc. Access to
youtube-dl can be configured with the `'uploads:use_downloader'` permission

Finally, in some cases the user might want to reuse one file between the
requests to save the bandwidth (for example, reverse search + consecutive
upload). In this case one should use [temporary file
uploads](#uploading-temporary-file), and pass the tokens returned by the API as
regular fields appended with a `Token` suffix. For example, to use previously
uploaded data, which was given token `deadbeef`, in an API that accepts a file
named `content`, the client should pass `{"contentToken":"deadbeef"}` as part
of the JSON message body. If the file with the particular token doesn't exist
or it has expired, the server will show an error.

## Error handling

All errors (except for unhandled fatal server errors) send relevant HTTP status
code together with JSON of following structure:

```json5
{
    "name": "Name of the error, e.g. 'PostNotFoundError'",
    "title": "Generic title of error message, e.g. 'Not found'",
    "description": "Detailed description of what went wrong, e.g. 'User `rr-` not found."
}
```

List of possible error names:

- `MissingRequiredFileError`
- `MissingRequiredParameterError`
- `InvalidParameterError` (when trying to pass text when integer is expected etc.)
- `IntegrityError` (race conditions when editing the same resource)
- `SearchError`
- `AuthError`
- `PostNotFoundError`
- `PostAlreadyFeaturedError`
- `PostAlreadyUploadedError`
- `InvalidPostIdError`
- `InvalidPostSafetyError`
- `InvalidPostSourceError`
- `InvalidPostContentError`
- `InvalidPostRelationError`
- `InvalidPostNoteError`
- `InvalidPostFlagError`
- `InvalidFavoriteTargetError`
- `InvalidCommentIdError`
- `CommentNotFoundError`
- `EmptyCommentTextError`
- `InvalidScoreTargetError`
- `InvalidScoreValueError`
- `TagCategoryNotFoundError`
- `TagCategoryAlreadyExistsError`
- `TagCategoryIsInUseError`
- `InvalidTagCategoryNameError`
- `InvalidTagCategoryColorError`
- `TagNotFoundError`
- `TagAlreadyExistsError`
- `TagIsInUseError`
- `InvalidTagNameError`
- `InvalidTagRelationError`
- `InvalidTagCategoryError`
- `InvalidTagDescriptionError`
- `UserNotFoundError`
- `UserAlreadyExistsError`
- `InvalidUserNameError`
- `InvalidEmailError`
- `InvalidPasswordError`
- `InvalidRankError`
- `InvalidAvatarError`
- `ProcessingError` (failed to generate thumbnail or download remote file)
- `ValidationError` (catch all for odd validation errors)


## Field selecting

For performance considerations, sometimes the client might want to choose the
fields the server sends to it in order to improve the query speed. This
customization is available for top-level fields of most of the
[resources](#resources). To choose the fields, the client should pass
`?fields=field1,field2,...` suffix to the query. This works regardless of the
request type (`GET`, `PUT` etc.).

For example, to list posts while getting only their IDs and tags, the client
should send a `GET` query like this:

```
GET /posts/?fields=id,tags
```

## Versioning

To prevent problems with concurrent resource modification, szurubooru
implements optimistic locks using resource versions. Each modifiable resource
has its `version` returned to the client with `GET` requests. At the same time,
each `PUT` and `DELETE` request sent by the client must present the same
`version` field to the server with value as it was given in `GET`.

For example, given `GET /post/1`, the server responds like this:

```
{
    ...,
    "version": 2
}
```

This means the client must then send `{"version": 2}` back too. If the client
fails to do so, the server will reject the request notifying about missing
parameter. If someone has edited the post in the mean time, the server will
reject the request as well, in which case the client is encouraged to notify
the user about the situation.


## Webhooks

System administrators can choose to configure webhooks to track events.
Webhook URIs can be configured in `config.yaml` (See `config.yaml.dist` for
example). Upon any event, the API will send a `POST` request to the listed
URIs with a [snapshot resource](#snapshot) generated with anonymous user
privileges as the message body, in JSON format.


# API reference

Depending on the deployment, the URLs might be relative to some base path such
as `/api/`. Values denoted with diamond braces (`<like this>`) signify variable
data.

## Listing tag categories
- **Request**

    `GET /tag-categories`

- **Output**

    An [unpaged search result](#unpaged-search-result), for which `<resource>`
    is a [tag category resource](#tag-category).

- **Errors**

    - privileges are too low

- **Description**

    Lists all tag categories. Doesn't use paging.

## Creating tag category
- **Request**

    `POST /tag-categories`

- **Input**

    ```json5
    {
        "name":  <name>,
        "color": <color>,
        "order": <order>  // optional
    }
    ```

- **Output**

    A [tag category resource](#tag-category).

- **Errors**

    - the name is used by an existing tag category (names are case insensitive)
    - the name is invalid or missing
    - the color is invalid or missing
    - privileges are too low

- **Description**

    Creates a new tag category using specified parameters. Name must match
    `tag_category_name_regex` from server's configuration. First category
    created becomes the default category.

## Updating tag category
- **Request**

    `PUT /tag-category/<name>`

- **Input**

    ```json5
    {
        "version": <version>,
        "name":    <name>,    // optional
        "color":   <color>,   // optional
        "order":   <order>    // optional
    }
    ```

- **Output**

    A [tag category resource](#tag-category).

- **Errors**

    - the version is outdated
    - the tag category does not exist
    - the name is used by an existing tag category (names are case insensitive)
    - the name is invalid
    - the color is invalid
    - privileges are too low

- **Description**

    Updates an existing tag category using specified parameters. Name must
    match `tag_category_name_regex` from server's configuration. All fields
    except the [`version`](#versioning) are optional - update concerns only
    provided fields.

## Getting tag category
- **Request**

    `GET /tag-category/<name>`

- **Output**

    A [tag category resource](#tag-category).

- **Errors**

    - the tag category does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing tag category.

## Deleting tag category
- **Request**

    `DELETE /tag-category/<name>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the tag category does not exist
    - the tag category is used by some tags
    - the tag category is the last tag category available
    - privileges are too low

- **Description**

    Deletes existing tag category. The tag category to be deleted must have no
    usages.

## Setting default tag category
- **Request**

    `PUT /tag-category/<name>/default`

- **Input**

    ```json5
    {}
    ```

- **Output**

    A [tag category resource](#tag-category).

- **Errors**

    - the tag category does not exist
    - privileges are too low

- **Description**

    Sets given tag category as default. All new tags created manually or
    automatically will have this category.

## Listing tags
- **Request**

    `GET /tags/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [tag resource](#tag).

- **Errors**

    - privileges are too low

- **Description**

    Searches for tags.

    **Anonymous tokens**

    Same as `name` token.

    **Named tokens**

    | `<key>`             | Description                               |
    | ------------------- | ----------------------------------------- |
    | `name`              | having given name (accepts wildcards)     |
    | `category`          | having given category (accepts wildcards) |
    | `creation-date`     | created at given date                     |
    | `creation-time`     | alias of `creation-date`                  |
    | `last-edit-date`    | edited at given date                      |
    | `last-edit-time`    | alias of `last-edit-date`                 |
    | `edit-date`         | alias of `last-edit-date`                 |
    | `edit-time`         | alias of `last-edit-date`                 |
    | `usages`            | used in given number of posts             |
    | `usage-count`       | alias of `usages`                         |
    | `post-count`        | alias of `usages`                         |
    | `suggestion-count`  | with given number of suggestions          |
    | `implication-count` | with given number of implications         |

    **Sort style tokens**

    | `<value>`           | Description                  |
    | ------------------- | ---------------------------- |
    | `random`            | as random as it can get      |
    | `name`              | A to Z                       |
    | `category`          | category (A to Z)            |
    | `creation-date`     | recently created first       |
    | `creation-time`     | alias of `creation-date`     |
    | `last-edit-date`    | recently edited first        |
    | `last-edit-time`    | alias of `creation-time`     |
    | `edit-date`         | alias of `creation-time`     |
    | `edit-time`         | alias of `creation-time`     |
    | `usages`            | used in most posts first     |
    | `usage-count`       | alias of `usages`            |
    | `post-count`        | alias of `usages`            |
    | `suggestion-count`  | with most suggestions first  |
    | `implication-count` | with most implications first |

    **Special tokens**

    None.

## Creating tag
- **Request**

    `POST /tags`

- **Input**

    ```json5
    {
        "names":        [<name1>, <name2>, ...],
        "category":     <category>,
        "description":  <description>,           // optional
        "implications": [<name1>, <name2>, ...], // optional
        "suggestions":  [<name1>, <name2>, ...]  // optional
    }
    ```

- **Output**

    A [tag resource](#tag).

- **Errors**

    - any name is used by an existing tag (names are case insensitive)
    - any name, implication or is invalid
    - category is invalid
    - no name was specified
    - implications or suggestions contain any item from names (e.g. there's a
      shallow cyclic dependency)
    - privileges are too low

- **Description**

    Creates a new tag using specified parameters. Names, suggestions and
    implications must match `tag_name_regex` from server's configuration.
    Category must exist and is the same as `name` field within
    [`<tag-category>` resource](#tag-category). Suggestions and implications
    are optional. If specified implied tags or suggested tags do not exist yet,
    they will be automatically created. Tags created automatically have no
    implications, no suggestions, one name and their category is set to the
    first tag category found. If there are no tag categories established yet,
    an error will be thrown.

## Updating tag
- **Request**

    `PUT /tag/<name>`

- **Input**

    ```json5
    {
        "version":      <version>,
        "names":        [<name1>, <name2>, ...], // optional
        "category":     <category>,              // optional
        "description":  <description>,           // optional
        "implications": [<name1>, <name2>, ...], // optional
        "suggestions":  [<name1>, <name2>, ...]  // optional
    }
    ```

- **Output**

    A [tag resource](#tag).

- **Errors**

    - the version is outdated
    - the tag does not exist
    - any name is used by an existing tag (names are case insensitive)
    - any name, implication or suggestion name is invalid
    - category is invalid
    - implications or suggestions contain any item from names (e.g. there's a
      shallow cyclic dependency)
    - privileges are too low

- **Description**

    Updates an existing tag using specified parameters. Names, suggestions and
    implications must match `tag_name_regex` from server's configuration.
    Category must exist and is the same as `name` field within
    [`<tag-category>` resource](#tag-category). If specified implied tags or
    suggested tags do not exist yet, they will be automatically created. Tags
    created automatically have no implications, no suggestions, one name and
    their category is set to the first tag category found. All fields except
    the [`version`](#versioning) are optional - update concerns only provided
    fields.

## Getting tag
- **Request**

    `GET /tag/<name>`

- **Output**

    A [tag resource](#tag).

- **Errors**

    - the tag does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing tag.

## Deleting tag
- **Request**

    `DELETE /tag/<name>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the tag does not exist
    - privileges are too low

- **Description**

    Deletes existing tag. The tag to be deleted must have no usages.

## Merging tags
- **Request**

    `POST /tag-merge/`

- **Input**

    ```json5
    {
        "removeVersion":  <source-tag-version>,
        "remove":         <source-tag-name>,
        "mergeToVersion": <target-tag-version>,
        "mergeTo":        <target-tag-name>
    }
    ```

- **Output**

    A [tag resource](#tag) containing the merged tag.

- **Errors**

    - the version of either tag is outdated
    - the source or target tag does not exist
    - the source tag is the same as the target tag
    - privileges are too low

- **Description**

    Removes source tag and merges all of its usages, suggestions and
    implications to the target tag. Other tag properties such as category and
    aliases do not get transferred and are discarded.

## Listing tag siblings
- **Request**

    `GET /tag-siblings/<name>`

- **Output**

    ```json5
    {
        "results": [
            {
                "tag": <tag>,
                "occurrences": <occurrence-count>
            },
            {
                "tag": <tag>,
                "occurrences": <occurrence-count>
            }
        ]
    }
    ```
    ...where `<tag>` is a [tag resource](#tag).

- **Errors**

    - privileges are too low

- **Description**

    Lists siblings of given tag, e.g. tags that were used in the same posts as
    the given tag. `occurrences` field signifies how many times a given sibling
    appears with given tag. Results are sorted by occurrences count and the
    list is truncated to the first 50 elements. Doesn't use paging.

## Listing posts
- **Request**

    `GET /posts/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [post resource](#post).

- **Errors**

    - privileges are too low

- **Description**

    Searches for posts.

    **Anonymous tokens**

    Same as `tag` token.

    **Named tokens**

    | `<key>`              | Description                                                                                                                             |
    | -------------------- | ----------------------------------------------------------                                                                              |
    | `id`                 | having given post number                                                                                                                |
    | `tag`                | having given tag (accepts wildcards)                                                                                                    |
    | `score`              | having given score                                                                                                                      |
    | `uploader`           | uploaded by given user (accepts wildcards)                                                                                              |
    | `upload`             | alias of upload                                                                                                                         |
    | `submit`             | alias of upload                                                                                                                         |
    | `comment`            | commented by given user (accepts wildcards)                                                                                             |
    | `fav`                | favorited by given user (accepts wildcards)                                                                                             |
    | `pool`               | belonging to the pool with the given ID                                                                                                 |
    | `tag-count`          | having given number of tags                                                                                                             |
    | `comment-count`      | having given number of comments                                                                                                         |
    | `fav-count`          | favorited by given number of users                                                                                                      |
    | `note-count`         | having given number of annotations                                                                                                      |
    | `note-text`          | having given note text (accepts wildcards)                                                                                              |
    | `relation-count`     | having given number of relations                                                                                                        |
    | `feature-count`      | having been featured given number of times                                                                                              |
    | `type`               | given type of posts. `<value>` can be either `image`, `animation` (or `animated` or `anim`), `flash` (or `swf`) or `video` (or `webm`). |
    | `content-checksum`   | having given SHA1 checksum                                                                                                              |
    | `file-size`          | having given file size (in bytes)                                                                                                       |
    | `image-width`        | having given image width (where applicable)                                                                                             |
    | `image-height`       | having given image height (where applicable)                                                                                            |
    | `image-area`         | having given number of pixels (image width * image height)                                                                              |
    | `image-aspect-ratio` | having given aspect ratio (image width / image height)                                                                                  |
    | `image-ar`           | alias of `image-aspect-ratio`                                                                                                           |
    | `width`              | alias of `image-width`                                                                                                                  |
    | `height`             | alias of `image-height`                                                                                                                 |
    | `area`               | alias of `image-area`                                                                                                                   |
    | `ar`                 | alias of `image-aspect-ratio`                                                                                                           |
    | `aspect-ratio`       | alias of `image-aspect-ratio`                                                                                                           |
    | `creation-date`      | posted at given date                                                                                                                    |
    | `creation-time`      | alias of `creation-date`                                                                                                                |
    | `date`               | alias of `creation-date`                                                                                                                |
    | `time`               | alias of `creation-date`                                                                                                                |
    | `last-edit-date`     | edited at given date                                                                                                                    |
    | `last-edit-time`     | alias of `last-edit-date`                                                                                                               |
    | `edit-date`          | alias of `last-edit-date`                                                                                                               |
    | `edit-time`          | alias of `last-edit-date`                                                                                                               |
    | `comment-date`       | commented at given date                                                                                                                 |
    | `comment-time`       | alias of `comment-date`                                                                                                                 |
    | `fav-date`           | last favorited at given date                                                                                                            |
    | `fav-time`           | alias of `fav-date`                                                                                                                     |
    | `feature-date`       | featured at given date                                                                                                                  |
    | `feature-time`       | alias of `feature-time`                                                                                                                 |
    | `safety`             | having given safety. `<value>` can be either `safe`, `sketchy` (or `questionable`) or `unsafe`.                                         |
    | `rating`             | alias of `safety`                                                                                                                       |

    **Sort style tokens**

    | `<value>`        | Description                                      |
    | ---------------- | ------------------------------------------------ |
    | `random`         | as random as it can get                          |
    | `id`             | highest to lowest post number                    |
    | `score`          | highest scored                                   |
    | `tag-count`      | with most tags                                   |
    | `comment-count`  | most commented first                             |
    | `fav-count`      | loved by most                                    |
    | `note-count`     | with most annotations                            |
    | `relation-count` | with most relations                              |
    | `feature-count`  | most often featured                              |
    | `file-size`      | largest files first                              |
    | `image-width`    | widest images first                              |
    | `image-height`   | tallest images first                             |
    | `image-area`     | largest images first                             |
    | `width`          | alias of `image-width`                           |
    | `height`         | alias of `image-height`                          |
    | `area`           | alias of `image-area`                            |
    | `creation-date`  | newest to oldest (pretty much same as id)        |
    | `creation-time`  | alias of `creation-date`                         |
    | `date`           | alias of `creation-date`                         |
    | `time`           | alias of `creation-date`                         |
    | `last-edit-date` | like creation-date, only looks at last edit time |
    | `last-edit-time` | alias of `last-edit-date`                        |
    | `edit-date`      | alias of `last-edit-date`                        |
    | `edit-time`      | alias of `last-edit-date`                        |
    | `comment-date`   | recently commented by anyone                     |
    | `comment-time`   | alias of `comment-date`                          |
    | `fav-date`       | recently added to favorites by anyone            |
    | `fav-time`       | alias of `fav-date`                              |
    | `feature-date`   | recently featured                                |
    | `feature-time`   | alias of `feature-time`                          |

    **Special tokens**

    | `<value>`    | Description                                                   |
    | ------------ | ------------------------------------------------------------- |
    | `liked`      | posts liked by currently logged in user                       |
    | `disliked`   | posts disliked by currently logged in user                    |
    | `fav`        | posts added to favorites by currently logged in user          |
    | `tumbleweed` | posts with score of 0, without comments and without favorites |

## Creating post
- **Request**

    `POST /posts/`

- **Input**

    ```json5
    {
        "tags":      [<tag1>, <tag2>, <tag3>],
        "safety":    <safety>,
        "source":    <source>,                    // optional
        "relations": [<post1>, <post2>, <post3>], // optional
        "notes":     [<note1>, <note2>, <note3>], // optional
        "flags":     [<flag1>, <flag2>],          // optional
        "anonymous": <anonymous>                  // optional
    }
    ```

- **Files**

    - `content` - the content of the post.
    - `thumbnail` - the content of custom thumbnail (optional).

- **Output**

    A [post resource](#post).

- **Errors**

    - tags have invalid names
    - safety, notes or flags are invalid
    - relations refer to non-existing posts
    - privileges are too low

- **Description**

    Creates a new post. If specified tags do not exist yet, they will be
    automatically created. Tags created automatically have no implications, no
    suggestions, one name and their category is set to the first tag category
    found. Safety must be any of `"safe"`, `"sketchy"` or `"unsafe"`. Relations
    must contain valid post IDs. If `<flag>` is omitted, they will be defined
    by default (`"loop"` will be set for all video posts, and `"sound"` will be
    auto-detected). Sending empty `thumbnail` will cause the post to use default
    thumbnail. If `anonymous` is set to truthy value, the uploader name won't be
    recorded (privilege verification still applies; it's possible to disallow
    anonymous uploads completely from config.) For details on how to pass `content`
    and `thumbnail`, see [file uploads](#file-uploads).

## Updating post
- **Request**

    `PUT /post/<id>`

- **Input**

    ```json5
    {
        "version":   <version>,
        "tags":      [<tag1>, <tag2>, <tag3>],    // optional
        "safety":    <safety>,                    // optional
        "source":    <source>,                    // optional
        "relations": [<post1>, <post2>, <post3>], // optional
        "notes":     [<note1>, <note2>, <note3>], // optional
        "flags":     [<flag1>, <flag2>]           // optional
    }
    ```

- **Files**

    - `content` - the content of the post (optional).
    - `thumbnail` - the content of custom thumbnail (optional).

- **Output**

    A [post resource](#post).

- **Errors**

    - the version is outdated
    - tags have invalid names
    - safety, notes or flags are invalid
    - relations refer to non-existing posts
    - privileges are too low

- **Description**

    Updates existing post. If specified tags do not exist yet, they will be
    automatically created. Tags created automatically have no implications, no
    suggestions, one name and their category is set to the first tag category
    found. Safety must be any of `"safe"`, `"sketchy"` or `"unsafe"`. Relations
    must contain valid post IDs. `<flag>` can be either `"loop"` to enable looping
    for video posts or `"sound"` to indicate sound. Sending empty `thumbnail` will
    reset the post thumbnail to default. For details how to pass `content` and
    `thumbnail`, see [file uploads](#file-uploads). All fields except the
    [`version`](#versioning) are optional - update concerns only provided
    fields.

## Getting post
- **Request**

    `GET /post/<id>`

- **Output**

    A [post resource](#post).

- **Errors**

    - the post does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing post.

## Getting around post
- **Request**

    `GET /post/<id>/around`

- **Output**

    ```json5
    {
        "prev":  <post-resource>,
        "next":  <post-resource>
    }
    ```

- **Errors**

    - the post does not exist
    - privileges are too low

- **Description**

    Retrieves information about posts that are before or after an existing post.

## Getting pools around post
- **Request**

    `GET /post/<id>/pools-nearby`

- **Output**

    ```json5
    [
        {
            "pool": <pool>,
            "firstPost": <first-post>,
            "lastPost": <last-post>,
            "nextPost": <next-post>,
            "previousPost": <previous-post>
        },
        ...
    ]
    ```

- **Field meaning**

- `<pool>`: The associated [micro pool resource](#micro-pool).
- `<first-post>`: A [micro post resource](#micro-post) that displays the first post in the pool.
- `<last-post>`: A [micro post resource](#micro-post) that displays the last post in the pool.
- `<next-post>`: A [micro post resource](#micro-post) that displays the next post in the pool.
- `<previous-post>`: A [micro post resource](#micro-post) that displays the previous post in the pool.

- **Errors**

    - the post does not exist
    - privileges are too low

- **Description**

    Retrieves extra information about any pools that the post is in.

## Deleting post
- **Request**

    `DELETE /post/<id>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the post does not exist
    - privileges are too low

- **Description**

    Deletes existing post. Related posts and tags are kept.

## Merging posts
- **Request**

    `POST /post-merge/`

- **Input**

    ```json5
    {
        "removeVersion":  <source-post-version>,
        "remove":         <source-post-id>,
        "mergeToVersion": <target-post-version>,
        "mergeTo":        <target-post-id>,
        "replaceContent": <true-or-false>
    }
    ```

- **Output**

    A [post resource](#post) containing the merged post.

- **Errors**

    - the version of either post is outdated
    - the source or target post does not exist
    - the source post is the same as the target post
    - privileges are too low

- **Description**

    Removes source post and merges all of its tags, relations, scores,
    favorites and comments to the target post. If `replaceContent` is set to
    true, content of the target post is replaced using the content of the
    source post; otherwise it remains unchanged. Source post properties such as
    its safety, source, whether to loop the video and other scalar values do
    not get transferred and are discarded.

## Rating post
- **Request**

    `PUT /post/<id>/score`

- **Input**

    ```json5
    {
        "score": <score>
    }
    ```

- **Output**

    A [post resource](#post).

- **Errors**

    - post does not exist
    - score is invalid
    - privileges are too low

- **Description**

    Updates score of authenticated user for given post. Valid scores are -1, 0
    and 1.

## Adding post to favorites
- **Request**

    `POST /post/<id>/favorite`

- **Output**

    A [post resource](#post).

- **Errors**

    - post does not exist
    - privileges are too low

- **Description**

    Marks the post as favorite for authenticated user.

## Removing post from favorites
- **Request**

    `DELETE /post/<id>/favorite`

- **Output**

    A [post resource](#post).

- **Errors**

    - post does not exist
    - privileges are too low

- **Description**

    Unmarks the post as favorite for authenticated user.

## Getting featured post
- **Request**

    `GET /featured-post`

- **Output**

    A [post resource](#post).

- **Errors**

    - privileges are too low

- **Description**

    Retrieves the post that is currently featured on the main page in web
    client. If no post is featured, `<post>` is null. Note that this method
    exists mostly for compatibility with setting featured post - most of times,
    you'd want to use query global info which contains more information.

## Featuring post
- **Request**

    `POST /featured-post`

- **Input**

    ```json5
    {
        "id": <post-id>
    }
    ```

- **Output**

    A [post resource](#post).

- **Errors**

    - privileges are too low
    - trying to feature a post that is currently featured

- **Description**

    Features a post on the main page in web client.

## Reverse image search
- **Request**

    `POST /posts/reverse-search`

- **Files**

    - `content` - the image to search for.

- **Output**

    An [image search result](#image-search-result).

- **Errors**

    - privileges are too low

- **Description**

    Retrieves posts that look like the input image.

## Listing pool categories
- **Request**

    `GET /pool-categories`

- **Output**

    An [unpaged search result](#unpaged-search-result), for which `<resource>`
    is a [pool category resource](#pool-category).

- **Errors**

    - privileges are too low

- **Description**

    Lists all pool categories. Doesn't use paging.

## Creating pool category
- **Request**

    `POST /pool-categories`

- **Input**

    ```json5
    {
        "name":  <name>,
        "color": <color>
    }
    ```

- **Output**

    A [pool category resource](#pool-category).

- **Errors**

    - the name is used by an existing pool category (names are case insensitive)
    - the name is invalid or missing
    - the color is invalid or missing
    - privileges are too low

- **Description**

    Creates a new pool category using specified parameters. Name must match
    `pool_category_name_regex` from server's configuration. First category
    created becomes the default category.

## Updating pool category
- **Request**

    `PUT /pool-category/<name>`

- **Input**

    ```json5
    {
        "version": <version>,
        "name":    <name>,    // optional
        "color":   <color>,   // optional
    }
    ```

- **Output**

    A [pool category resource](#pool-category).

- **Errors**

    - the version is outdated
    - the pool category does not exist
    - the name is used by an existing pool category (names are case insensitive)
    - the name is invalid
    - the color is invalid
    - privileges are too low

- **Description**

    Updates an existing pool category using specified parameters. Name must
    match `pool_category_name_regex` from server's configuration. All fields
    except the [`version`](#versioning) are optional - update concerns only
    provided fields.

## Getting pool category
- **Request**

    `GET /pool-category/<name>`

- **Output**

    A [pool category resource](#pool-category).

- **Errors**

    - the pool category does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing pool category.

## Deleting pool category
- **Request**

    `DELETE /pool-category/<name>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the pool category does not exist
    - the pool category is used by some pools
    - the pool category is the last pool category available
    - privileges are too low

- **Description**

    Deletes existing pool category. The pool category to be deleted must have no
    usages.

## Setting default pool category
- **Request**

    `PUT /pool-category/<name>/default`

- **Input**

    ```json5
    {}
    ```

- **Output**

    A [pool category resource](#pool-category).

- **Errors**

    - the pool category does not exist
    - privileges are too low

- **Description**

    Sets given pool category as default. All new pools created manually or
    automatically will have this category.

## Listing pools
- **Request**

    `GET /pools/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [pool resource](#pool).

- **Errors**

    - privileges are too low

- **Description**

    Searches for pools.

    **Anonymous tokens**

    Same as `name` token.

    **Named tokens**

    | `<key>`             | Description                               |
    | ------------------- | ----------------------------------------- |
    | `name`              | having given name (accepts wildcards)     |
    | `category`          | having given category (accepts wildcards) |
    | `creation-date`     | created at given date                     |
    | `creation-time`     | alias of `creation-date`                  |
    | `last-edit-date`    | edited at given date                      |
    | `last-edit-time`    | alias of `last-edit-date`                 |
    | `edit-date`         | alias of `last-edit-date`                 |
    | `edit-time`         | alias of `last-edit-date`                 |
    | `post-count`        | used in given number of posts             |

    **Sort style tokens**

    | `<value>`           | Description                  |
    | ------------------- | ---------------------------- |
    | `random`            | as random as it can get      |
    | `name`              | A to Z                       |
    | `category`          | category (A to Z)            |
    | `creation-date`     | recently created first       |
    | `creation-time`     | alias of `creation-date`     |
    | `last-edit-date`    | recently edited first        |
    | `last-edit-time`    | alias of `creation-time`     |
    | `edit-date`         | alias of `creation-time`     |
    | `edit-time`         | alias of `creation-time`     |
    | `post-count`        | used in most posts first     |

    **Special tokens**

    None.

## Creating pool
- **Request**

    `POST /pools/create`

- **Input**

    ```json5
    {
        "names":        [<name1>, <name2>, ...],
        "category":     <category>,
        "description":  <description>,           // optional
        "posts":        [<id1>, <id2>, ...],     // optional
    }
    ```

- **Output**

    A [pool resource](#pool).

- **Errors**

    - any name is invalid
    - category is invalid
    - no name was specified
    - there is at least one duplicate post
    - at least one post ID does not exist
    - privileges are too low

- **Description**

    Creates a new pool using specified parameters. Names, suggestions and
    implications must match `pool_name_regex` from server's configuration.
    Category must exist and is the same as `name` field within
    [`<pool-category>` resource](#pool-category). `posts` is an optional list of
    integer post IDs. If the specified posts do not exist, an error will be
    thrown.

## Updating pool
- **Request**

    `PUT /pool/<id>`

- **Input**

    ```json5
    {
        "version":      <version>,
        "names":        [<name1>, <name2>, ...], // optional
        "category":     <category>,              // optional
        "description":  <description>,           // optional
        "posts":        [<id1>, <id2>, ...],     // optional
    }
    ```

- **Output**

    A [pool resource](#pool).

- **Errors**

    - the version is outdated
    - the pool does not exist
    - any name is invalid
    - category is invalid
    - no name was specified
    - there is at least one duplicate post
    - at least one post ID does not exist
    - privileges are too low

- **Description**

    Updates an existing pool using specified parameters. Names, suggestions and
    implications must match `pool_name_regex` from server's configuration.
    Category must exist and is the same as `name` field within
    [`<pool-category>` resource](#pool-category). `posts` is an optional list of
    integer post IDs. If the specified posts do not exist yet, an error will be
    thrown. The full list of post IDs must be provided if they are being
    updated, and the previous list of posts will be replaced with the new one.
    All fields except the [`version`](#versioning) are optional - update
    concerns only provided fields.

## Getting pool
- **Request**

    `GET /pool/<id>`

- **Output**

    A [pool resource](#pool).

- **Errors**

    - the pool does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing pool.

## Deleting pool
- **Request**

    `DELETE /pool/<name>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the pool does not exist
    - privileges are too low

- **Description**

    Deletes existing pool. All posts in the pool will only have their relation
    to the pool removed.

## Merging pools
- **Request**

    `POST /pool-merge/`

- **Input**

    ```json5
    {
        "removeVersion":  <source-pool-version>,
        "remove":         <source-pool-id>,
        "mergeToVersion": <target-pool-version>,
        "mergeTo":        <target-pool-id>
    }
    ```

- **Output**

    A [pool resource](#pool) containing the merged pool.

- **Errors**

    - the version of either pool is outdated
    - the source or target pool does not exist
    - the source pool is the same as the target pool
    - privileges are too low

- **Description**

    Removes source pool and merges all of its posts with the target pool. Other
    pool properties such as category and aliases do not get transferred and are
    discarded.

## Listing comments
- **Request**

    `GET /comments/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [comment resource](#comment).

- **Errors**

    - privileges are too low

- **Description**

    Searches for comments.

    **Anonymous tokens**

    Same as `text` token.

    **Named tokens**

    | `<key>`          | Description                                    |
    | ---------------- | ---------------------------------------------- |
    | `id`             | specific comment ID                            |
    | `post`           | specific post ID                               |
    | `user`           | created by given user (accepts wildcards)      |
    | `author`         | alias of `user`                                |
    | `text`           | containing given text (accepts wildcards)      |
    | `creation-date`  | created at given date                          |
    | `creation-time`  | alias of `creation-date`                       |
    | `last-edit-date` | whose most recent edit date matches given date |
    | `last-edit-time` | alias of `last-edit-date`                      |
    | `edit-date`      | alias of `last-edit-date`                      |
    | `edit-time`      | alias of `last-edit-date`                      |

    **Sort style tokens**

    | `<value>`        | Description               |
    | ---------------- | ------------------------- |
    | `random`         | as random as it can get   |
    | `user`           | author name, A to Z       |
    | `author`         | alias of `user`           |
    | `post`           | post ID, newest to oldest |
    | `creation-date`  | newest to oldest          |
    | `creation-time`  | alias of `creation-date`  |
    | `last-edit-date` | recently edited first     |
    | `last-edit-time` | alias of `last-edit-date` |
    | `edit-date`      | alias of `last-edit-date` |
    | `edit-time`      | alias of `last-edit-date` |

    **Special tokens**

    None.

## Creating comment
- **Request**

    `POST /comments/`

- **Input**

    ```json5
    {
        "text":   <text>,
        "postId": <post-id>
    }
    ```

- **Output**

    A [comment resource](#comment).

- **Errors**

    - the post does not exist
    - comment text is empty
    - privileges are too low

- **Description**

    Creates a new comment under given post.

## Updating comment
- **Request**

    `PUT /comment/<id>`

- **Input**

    ```json5
    {
        "version": <version>,
        "text":    <new-text> // mandatory
    }
    ```

- **Output**

    A [comment resource](#comment).

- **Errors**

    - the version is outdated
    - the comment does not exist
    - new comment text is empty
    - privileges are too low

- **Description**

    Updates an existing comment text.

## Getting comment
- **Request**

    `GET /comment/<id>`

- **Output**

    A [comment resource](#comment).

- **Errors**

    - the comment does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing comment.

## Deleting comment
- **Request**

    `DELETE /comment/<id>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the comment does not exist
    - privileges are too low

- **Description**

    Deletes existing comment.

## Rating comment
- **Request**

    `PUT /comment/<id>/score`

- **Input**

    ```json5
    {
        "score": <score>
    }
    ```

- **Output**

    A [comment resource](#comment).

- **Errors**

    - comment does not exist
    - score is invalid
    - privileges are too low

- **Description**

    Updates score of authenticated user for given comment. Valid scores are -1,
    0 and 1.

## Listing users
- **Request**

    `GET /users/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [user resource](#user).

- **Errors**

    - privileges are too low

- **Description**

    Searches for users.

    **Anonymous tokens**

    Same as `name` token.

    **Named tokens**

    | `<key>`           | Description                                     |
    | ----------------- | ----------------------------------------------- |
    | `name`            | having given name (accepts wildcards)           |
    | `creation-date`   | registered at given date                        |
    | `creation-time`   | alias of `creation-date`                        |
    | `last-login-date` | whose most recent login date matches given date |
    | `last-login-time` | alias of `last-login-date`                      |
    | `login-date`      | alias of `last-login-date`                      |
    | `login-time`      | alias of `last-login-date`                      |

    **Sort style tokens**

    | `<value>`         | Description                |
    | ----------------- | -------------------------- |
    | `random`          | as random as it can get    |
    | `name`            | A to Z                     |
    | `creation-date`   | newest to oldest           |
    | `creation-time`   | alias of `creation-date`   |
    | `last-login-date` | recently active first      |
    | `last-login-time` | alias of `last-login-date` |
    | `login-date`      | alias of `last-login-date` |
    | `login-time`      | alias of `last-login-date` |

    **Special tokens**

    None.

## Creating user
- **Request**

    `POST /users`

- **Input**

    ```json5
    {
        "name":        <user-name>,
        "password":    <user-password>,
        "email":       <email>,         // optional
        "rank":        <rank>,          // optional
        "avatarStyle": <avatar-style>   // optional
    }
    ```

- **Files**

    - `avatar` - the content of the new avatar (optional).

- **Output**

    A [user resource](#user).

- **Errors**

    - a user with such name already exists (names are case insensitive)
    - either user name, password, email or rank are invalid
    - the user is trying to update their or someone else's rank to higher than
      their own
    - avatar is missing for manual avatar style
    - privileges are too low

- **Description**

    Creates a new user using specified parameters. Names and passwords must
    match `user_name_regex` and `password_regex` from server's configuration,
    respectively. Email address, rank and avatar fields are optional. Avatar
    style can be either `gravatar` or `manual`. `manual` avatar style requires
    client to pass also `avatar` file - see [file uploads](#file-uploads) for
    details. If the rank is empty and the user happens to be the first user
    ever created, become an administrator, whereas subsequent users will be
    given the rank indicated by `default_rank` in the server's configuration.

## Updating user
- **Request**

    `PUT /user/<name>`

- **Input**

    ```json5
    {
        "version":     <version>,
        "name":        <user-name>,     // optional
        "password":    <user-password>, // optional
        "email":       <email>,         // optional
        "rank":        <rank>,          // optional
        "avatarStyle": <avatar-style>   // optional
    }
    ```

- **Files**

    - `avatar` - the content of the new avatar (optional).

- **Output**

    A [user resource](#user).

- **Errors**

    - the version is outdated
    - the user does not exist
    - a user with new name already exists (names are case insensitive)
    - either user name, password, email or rank are invalid
    - the user is trying to update their or someone else's rank to higher than
      their own
    - avatar is missing for manual avatar style
    - privileges are too low

- **Description**

    Updates an existing user using specified parameters. Names and passwords
    must match `user_name_regex` and `password_regex` from server's
    configuration, respectively. All fields are optional - update concerns only
    provided fields. To update last login time, see
    [authentication](#authentication). Avatar style can be either `gravatar` or
    `manual`. `manual` avatar style requires client to pass also `avatar`
    file - see [file uploads](#file-uploads) for details. All fields except the
    [`version`](#versioning) are optional - update concerns only provided
    fields.

## Getting user
- **Request**

    `GET /user/<name>`

- **Output**

    A [user resource](#user).

- **Errors**

    - the user does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing user.

## Deleting user
- **Request**

    `DELETE /user/<name>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the version is outdated
    - the user does not exist
    - privileges are too low

- **Description**

    Deletes existing user.

## Listing user tokens
- **Request**

    `GET /user-tokens/<user_name>`

- **Output**

    An [unpaged search result resource](#unpaged-search-result), for which
    `<resource>` is a [user token resource](#user-token).

- **Errors**

    - privileges are too low

- **Description**

    Searches for user tokens for the given user.

## Creating user token
- **Request**

    `POST /user-token/<user_name>`

- **Input**

    ```json5
    {
        "enabled":        <enabled>,        // optional
        "note":           <note>,           // optional
        "expirationTime": <expiration-time> // optional
    }
    ```

- **Output**

    A [user token resource](#user-token).

- **Errors**

    - privileges are too low

- **Description**

    Creates a new user token that can be used for authentication of API
    endpoints instead of a password.

## Updating user token
- **Request**

    `PUT /user-token/<user_name>/<token>`

- **Input**

    ```json5
    {
        "version":        <version>,
        "enabled":        <enabled>,        // optional
        "note":           <note>,           // optional
        "expirationTime": <expiration-time> // optional
    }
    ```

- **Output**

    A [user token resource](#user-token).

- **Errors**

    - the version is outdated
    - the user token does not exist
    - privileges are too low

- **Description**

    Updates an existing user token using specified parameters. All fields
    except the [`version`](#versioning) are optional - update concerns only
    provided fields.

## Deleting user token
- **Request**

    `DELETE /user-token/<user_name>/<token>`

- **Input**

    ```json5
    {
        "version": <version>
    }
    ```

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the token does not exist
    - privileges are too low

- **Description**

    Deletes existing user token.

## Password reset - step 1: mail request
- **Request**

    `GET /password-reset/<email-or-name>`

- **Output**

    ```
    {}
    ```

- **Errors**

    - the user does not exist
    - the user hasn't provided an email address

- **Description**

    Sends a confirmation email to given user. The email contains link
    containing a token. The token cannot be guessed, thus using such link
    proves that the person who requested to reset the password also owns the
    mailbox, which is a strong indication they are the rightful owner of the
    account.

## Password reset - step 2: confirmation
- **Request**

    `POST /password-reset/<email-or-name>`

- **Input**

    ```json5
    {
        "token": <token-from-email>
    }
    ```

- **Output**

    ```json5
    {
        "password": <new-password>
    }
    ```

- **Errors**

    - the token is missing
    - the token is invalid
    - the user does not exist

- **Description**

    Generates a new password for given user. Password is sent as plain-text, so
    it is recommended to connect through HTTPS.

## Listing snapshots
- **Request**

    `GET /snapshots/?offset=<initial-pos>&limit=<page-size>&query=<query>`

- **Output**

    A [paged search result resource](#paged-search-result), for which
    `<resource>` is a [snapshot resource](#snapshot).

- **Errors**

    - privileges are too low

- **Description**

    Lists recent resource snapshots.

    **Anonymous tokens**

    Not supported.

    **Named tokens**

    | `<key>`           | Description                                                      |
    | ----------------- | ---------------------------------------------------------------- |
    | `type`            | involving given resource type                                    |
    | `id`              | involving given resource id                                      |
    | `date`            | created at given date                                            |
    | `time`            | alias of `date`                                                  |
    | `operation`       | `modified`, `created`, `deleted` or `merged`                     |
    | `user`            | name of the user that created given snapshot (accepts wildcards) |

    **Sort style tokens**

    None. The snapshots are always sorted by creation time.

    **Special tokens**

    None.

## Getting global info
- **Request**

    `GET /info`

- **Output**

    ```json5
    {
        "postCount": <post-count>,
        "diskUsage": <disk-usage>,  // in bytes
        "featuredPost": <featured-post>,
        "featuringTime": <time>,
        "featuringUser": <user>,
        "serverTime": <server-time>,
        "config": {
            "userNameRegex": <user-name-regex>,
            "passwordRegex": <password-regex>,
            "tagNameRegex": <tag-name-regex>,
            "tagCategoryNameRegex": <tag-category-name-regex>,
            "defaultUserRank": <default-rank>,
            "privileges": <privileges>
        }
    }
    ```

- **Description**

    Retrieves simple statistics. `<featured-post>` is null if there is no
    featured post yet. `<server-time>` is pretty much the same as the `Date`
    HTTP field, only formatted in a manner consistent with other dates. Values
    in `config` key are taken directly from the server config, with the
    exception of privilege array keys being converted to lower camel case to
    match the API convention.

## Uploading temporary file

- **Request**

    `POST /uploads`

- **Files**

    - `content` - the content of the file to upload. Note that in this
      particular API, one can't use token-based uploads.

- **Output**

    ```json5
    {
        "token": <token>
    }
    ```

- **Errors**

    - privileges are too low

- **Description**

    Puts a file in temporary storage and assigns it a token that can be used in
    other requests. The files uploaded that way are deleted after a short while
    so clients shouldn't use it as a free upload service.



# Resources

## User
**Description**

A single user.

**Structure**

```json5
{
    "version":           <version>,
    "name":              <name>,
    "email":             <email>,
    "rank":              <rank>,
    "lastLoginTime":     <last-login-time>,
    "creationTime":      <creation-time>,
    "avatarStyle":       <avatar-style>,
    "avatarUrl":         <avatar-url>,
    "commentCount":      <comment-count>,
    "uploadedPostCount": <uploaded-post-count>,
    "likedPostCount":    <liked-post-count>,
    "dislikedPostCount": <disliked-post-count>,
    "favoritePostCount": <favorite-post-count>
}
```

**Field meaning**
- `<version>`: resource version. See [versioning](#versioning).
- `<name>`: the user name.
- `<email>`: the user email. It is available only if the request is
  authenticated by the same user, or the authenticated user can change the
  email. If it's unavailable, the server returns `false`. If the user hasn't
  specified an email, the server returns `null`.
- `<rank>`: the user rank, which effectively affects their privileges.

    Possible values:

    - `"restricted"`: restricted user
    - `"regular"`: regular user
    - `"power"`: power user
    - `"moderator"`: moderator
    - `"administrator"`: administrator

- `<last-login-time>`: the last login time, formatted as per RFC 3339.
- `<creation-time>`: the user registration time, formatted as per RFC 3339.
- `<avatarStyle>`: how to render the user avatar.

    Possible values:

    - `"gravatar"`: the user uses Gravatar.
    - `"manual"`: the user has uploaded a picture manually.

- `<avatarUrl>`: the URL to the avatar.
- `<comment-count>`: number of comments.
- `<uploaded-post-count>`: number of uploaded posts.
- `<liked-post-count>`: nubmer of liked posts. It is available only if the
  request is authenticated by the same user. If it's unavailable, the server
  returns `false`.
- `<disliked-post-count>`: number of disliked posts. It is available only if
  the request is authenticated by the same user. If it's unavailable, the
  server returns `false`.
- `<favorite-post-count>`: number of favorited posts.

## Micro user
**Description**

A [user resource](#user) stripped down to `name` and `avatarUrl` fields.

## User token
**Description**

A single user token.

**Structure**

```json5
{
    "user":           <user>,
    "token":          <token>,
    "note":           <token>,
    "enabled":        <enabled>,
    "expirationTime": <expiration-time>,
    "version":        <version>,
    "creationTime":   <creation-time>,
    "lastEditTime":   <last-edit-time>,
    "lastUsageTime":  <last-usage-time>
}
```

**Field meaning**
- `<user>`: micro user. See [micro user](#micro-user).
- `<token>`: the token that can be used to authenticate the user.
- `<note>`: a note that describes the token.
- `<enabled>`: whether the token is still valid for authentication.
- `<expiration-time>`: time when the token expires. It must include the timezone as per RFC 3339.
- `<version>`: resource version. See [versioning](#versioning).
- `<creation-time>`: time the user token was created, formatted as per RFC 3339.
- `<last-edit-time>`: time the user token was edited, formatted as per RFC 3339.
- `<last-usage-time>`: the last time this token was used during a login involving `?bump-login`, formatted as per RFC 3339.

## Tag category
**Description**

A single tag category. The primary purpose of tag categories is to distinguish
certain tag types (such as characters, media type etc.), which improves user
experience.

**Structure**

```json5
{
    "version": <version>,
    "name":    <name>,
    "color":   <color>,
    "usages":  <usages>,
    "order":   <order>,
    "default": <is-default>
}
```

**Field meaning**

- `<version>`: resource version. See [versioning](#versioning).
- `<name>`: the category name.
- `<color>`: the category color.
- `<usages>`: how many tags is the given category used with.
- `<order>`: the order in which tags with this category are displayed, ascending.
- `<is-default>`: whether the tag category is the default one.

## Tag
**Description**

A single tag. Tags are used to let users search for posts.

**Structure**

```json5
{
    "version":      <version>,
    "names":        <names>,
    "category":     <category>,
    "implications": <implications>,
    "suggestions":  <suggestions>,
    "creationTime": <creation-time>,
    "lastEditTime": <last-edit-time>,
    "usages":       <usage-count>,
    "description":  <description>
}
```

**Field meaning**

- `<version>`: resource version. See [versioning](#versioning).
- `<names>`: a list of tag names (aliases). Tagging a post with any name will
  automatically assign the first name from this list.
- `<category>`: the name of the category the given tag belongs to.
- `<implications>`: a list of implied tags, serialized as [micro
  tag resource](#micro-tag). Implied tags are automatically appended by the web
  client on usage.
- `<suggestions>`: a list of suggested tags, serialized as [micro
  tag resource](#micro-tag). Suggested tags are shown to the user by the web
  client on usage.
- `<creation-time>`: time the tag was created, formatted as per RFC 3339.
- `<last-edit-time>`: time the tag was edited, formatted as per RFC 3339.
- `<usage-count>`: the number of posts the tag was used in.
- `<description>`: the tag description (instructions how to use, history etc.)
  The client should render is as Markdown.

## Micro tag
**Description**

A [tag resource](#tag) stripped down to `names`, `category` and `usages` fields.

## Post
**Description**

One file together with its metadata posted to the site.

**Structure**

```json5
{
    "version":            <version>,
    "id":                 <id>,
    "creationTime":       <creation-time>,
    "lastEditTime":       <last-edit-time>,
    "safety":             <safety>,
    "source":             <source>,
    "type":               <type>,
    "checksum":           <checksum>,
    "checksumMD5":        <checksum-MD5>,
    "canvasWidth":        <canvas-width>,
    "canvasHeight":       <canvas-height>,
    "contentUrl":         <content-url>,
    "thumbnailUrl":       <thumbnail-url>,
    "flags":              <flags>,
    "tags":               <tags>,
    "relations":          <relations>,
    "notes":              <notes>,
    "user":               <user>,
    "score":              <score>,
    "ownScore":           <own-score>,
    "ownFavorite":        <own-favorite>,
    "tagCount":           <tag-count>,
    "favoriteCount":      <favorite-count>,
    "commentCount":       <comment-count>,
    "noteCount":          <note-count>,
    "featureCount":       <feature-count>,
    "relationCount":      <relation-count>,
    "lastFeatureTime":    <last-feature-time>,
    "favoritedBy":        <favorited-by>,
    "hasCustomThumbnail": <has-custom-thumbnail>,
    "mimeType":           <mime-type>,
    "comments": [
        <comment>,
        <comment>,
        <comment>
    ],
    "pools": [
        <pool>,
        <pool>,
        <pool>
    ]
}
```

**Field meaning**

- `<version>`: resource version. See [versioning](#versioning).
- `<id>`: the post identifier.
- `<creation-time>`: time the tag was created, formatted as per RFC 3339.
- `<last-edit-time>`: time the tag was edited, formatted as per RFC 3339.
- `<safety>`: whether the post is safe for work.

    Available values:

    - `"safe"`
    - `"sketchy"`
    - `"unsafe"`

- `<source>`: where the post was grabbed form, supplied by the user.
- `<type>`: the type of the post.

    Available values:

    - `"image"` - plain image.
    - `"animation"` - animated image (GIF).
    - `"video"` - WEBM video.
    - `"flash"` - Flash animation / game.
    - `"youtube"` - Youtube embed.

- `<checksum>`: the SHA1 file checksum. Used in snapshots to signify changes of the
  post content.
- `<checksum-MD5>`: the MD5 file checksum.
- `<canvas-width>` and `<canvas-height>`: the original width and height of the
  post content.
- `<content-url>`: where the post content is located.
- `<thumbnail-url>`: where the post thumbnail is located.
- `<flags>`: various flags such as whether the post is looped, represented as
  array of plain strings.
- `<tags>`: list of tags the post is tagged with, serialized as [micro
  tag resource](#micro-tag).
- `<relations>`: a list of related posts, serialized as [micro post
  resources](#micro-post). Links to related posts are shown
  to the user by the web client.
- `<notes>`: a list of post annotations, serialized as list of [note
  resources](#note).
- `<user>`: who created the post, serialized as [micro user resource](#micro-user).
- `<score>`: the collective score (+1/-1 rating) of the given post.
- `<own-score>`: the score (+1/-1 rating) of the given post by the
  authenticated user.
- `<own-favorite>`: whether the authenticated user has given post in their
  favorites.
- `<tag-count>`: how many tags the post is tagged with
- `<favorite-count>`: how many users have the post in their favorites
- `<comment-count>`: how many comments are filed under that post
- `<note-count>`: how many notes the post has
- `<feature-count>`: how many times has the post been featured.
- `<relation-count>`: how many posts are related to this post.
- `<last-feature-time>`: the last time the post was featured, formatted as per
  RFC 3339.
- `<favorited-by>`: list of users, serialized as [micro user resources](#micro-user).
- `<has-custom-thumbnail>`: whether the post uses custom thumbnail.
- `<mime-type>`: subsidiary to `<type>`, used to tell exact content format;
  useful for `<video>` tags for instance.
- `<comment>`: a [comment resource](#comment) for given post.
- `<pool>`: a [micro pool resource](#micro-pool) in which the post is a member of.

## Micro post
**Description**

A [post resource](#post) stripped down to `name` and `thumbnailUrl` fields.

## Note
**Description**

A text annotation rendered on top of the post.

**Structure**

```json5
{
    "polygon": <list-of-points>,
    "text":    <text>,
}
```

**Field meaning**
- `<list-of-points>`: where to draw the annotation. Each point must have
  coordinates within 0 to 1. For example, `[[0,0],[0,1],[1,1],[1,0]]` will draw
  the annotation on the whole post, whereas `[[0,0],[0,0.5],[0.5,0.5],[0.5,0]]`
  will draw it inside the post's upper left quarter.
- `<text>`: the annotation text. The client should render is as Markdown.

## Pool category
**Description**

A single pool category. The primary purpose of pool categories is to distinguish
certain pool types (such as series, relations etc.), which improves user
experience.

**Structure**

```json5
{
    "version": <version>,
    "name":    <name>,
    "color":   <color>,
    "usages":  <usages>,
    "default": <is-default>
}
```

**Field meaning**

- `<version>`: resource version. See [versioning](#versioning).
- `<name>`: the category name.
- `<color>`: the category color.
- `<usages>`: how many pools is the given category used with.
- `<is-default>`: whether the pool category is the default one.

## Pool
**Description**

An ordered list of posts, with a description and category.

**Structure**

```json5
{
    "version":      <version>,
    "id":           <id>,
    "names":        <names>,
    "category":     <category>,
    "posts":        <suggestions>,
    "creationTime": <creation-time>,
    "lastEditTime": <last-edit-time>,
    "postCount":   <post-count>,
    "description":  <description>
}
```

**Field meaning**

- `<version>`: resource version. See [versioning](#versioning).
- `<id>`: the pool identifier.
- `<names>`: a list of pool names (aliases).
- `<category>`: the name of the category the given pool belongs to.
- `<posts>`: an ordered list of posts, serialized as [micro
  post resource](#micro-post). Posts are ordered by insertion by default.
- `<creation-time>`: time the pool was created, formatted as per RFC 3339.
- `<last-edit-time>`: time the pool was edited, formatted as per RFC 3339.
- `<post-count>`: the number of posts the pool has.
- `<description>`: the pool description (instructions how to use, history etc.)
  The client should render it as Markdown.

## Micro pool
**Description**

A [pool resource](#pool) stripped down to `id`, `names`, `category`,
`description` and `postCount` fields.

## Comment
**Description**

A comment under a post.

**Structure**

```json5
{
    "version":      <version>,
    "id":           <id>,
    "postId":       <post-id>,
    "user":         <author>,
    "text":         <text>,
    "creationTime": <creation-time>,
    "lastEditTime": <last-edit-time>,
    "score":        <score>,
    "ownScore":     <own-score>
}
```

**Field meaning**
- `<version>`: resource version. See [versioning](#versioning).
- `<id>`: the comment identifier.
- `<post-id>`: an id of the post the comment is for.
- `<text>`: the comment content. The client should render is as Markdown.
- `<author>`: a [micro user resource](#micro-user) the comment is created by.
- `<creation-time>`: time the comment was created, formatted as per RFC 3339.
- `<last-edit-time>`: time the comment was edited, formatted as per RFC 3339.
- `<score>`: the collective score (+1/-1 rating) of the given comment.
- `<own-score>`: the score (+1/-1 rating) of the given comment by the
  authenticated user.

## Snapshot
**Description**

A snapshot is a version of a database resource.

**Structure**

```json5
{
    "operation": <operation>,
    "type":      <resource-type>,
    "id":        <resource-id>,
    "user":      <issuer>,
    "data":      <data>,
    "time":      <time>
}
```

**Field meaning**

- `<operation>`: what happened to the resource.

    The value can be either of values below:

    - `"created"` - the resource has been created
    - `"modified"` - the resource has been modified
    - `"deleted"` - the resource has been deleted
    - `"merged"` - the resource has been merged to another resource

- `<resource-type>` and `<resource-id>`: the resource that was changed.

    The values are correlated as per table below:

    | `<resource-type>` | `<resource-id>`                 |
    | ----------------- | ------------------------------- |
    | `"tag"`           | first tag name at given time    |
    | `"tag_category"`  | tag category name at given time |
    | `"post"`          | post ID                         |
    | `"pool"`          | pool ID                         |

- `<issuer>`: a [micro user resource](#micro-user) representing the user who
    has made the change.

- `<data>`: the snapshot data, of which content depends on the `<operation>`.
   More explained later.

- `<time>`: when the snapshot was created (i.e. when the resource was changed),
  formatted as per RFC 3339.

**`<data>` field for creation snapshots**

The value can be either of structures below, depending on
`<resource-type>`:

- Tag category snapshot data (`<resource-type> = "tag_category"`)

    *Example*

    ```json5
    {
        "name":  "character",
        "color": "#FF0000",
        "default": false
    }
    ```

- Tag snapshot data (`<resource-type> = "tag"`)

    *Example*

    ```json5
    {
        "names":        ["tag1", "tag2", "tag3"],
        "category":     "plain",
        "implications": ["imp1", "imp2", "imp3"],
        "suggestions":  ["sug1", "sug2", "sug3"]
    }
    ```

- Post snapshot data (`<resource-type> = "post"`)

    *Example*

    ```json5
    {
        "source": "http://example.com/",
        "safety": "safe",
        "checksum": "deadbeef",
        "tags": ["tag1", "tag2"],
        "relations": [1, 2],
        "notes": [<note1>, <note2>, <note3>],
        "flags": ["loop"],
        "featured": false
    }
    ```

    `<note>`s are serialized the same way as [note resources](#note).

**`<data>` field for modification snapshots**

The value is a property-wise recursive diff between previous version of the
resource and its current version. Its structure is a `<dictionary-diff>` of
dictionaries as created by creation snapshots, which is described below.

`<primitive>`: any primitive (number or a string)

`<anything>`: any dictionary, list or primitive

`<dictionary-diff>`:

```json5
{
    "type": "object change",
    "value":
    {
        "property-of-any-type-1":
        {
            "type": "deleted property",
            "value": <anything>
        },
        "property-of-any-type-2":
        {
            "type": "added property",
            "value": <anything>
        },
        "primitive-property":
        {
            "type": "primitive change",
            "old-value": "<primitive>",
            "new-value": "<primitive>"
        },
        "list-property": <list-diff>,
        "dictionary-property": <dictionary-diff>
    }
}
```

`<list-diff>`:

```json5
{
    "type": "list change",
    "removed": [<anything>, <anything>],
    "added": [<anything>, <anything>]
}
```

Example - a diff for a post that has changed source and has one note added.
Note the similarities with the structure of post creation snapshots.

```json5
{
    "type": "object change",
    "value":
    {
        "source":
        {
            "type": "primitive change",
            "old-value": None,
            "new-value": "new source"
        },
        "notes":
        {
            "type": "list change",
            "removed": [],
            "added":
            [
                {"polygon": [[0, 0], [0, 1], [1, 1]], "text": "new note"}
            ]
        }
    }
}
```

Since the snapshot dictionaries structure is pretty immutable, you probably
won't see `added property` or `deleted property` around. This observation holds
true even if the way the snapshots are generated changes - szurubooru stores
just the diffs rather than original snapshots, so it wouldn't be able to
generate a diff against an old version.

**`<data>` field for deletion snapshots**

Same as creation snapshot. In emergencies, it can be used to reconstruct
deleted entities. Please note that this does not constitute as means against
vandalism (it's still possible to cause chaos by mass editing - this should be
dealt with by configuring role privileges in the config) or replace database
backups.

**`<data>` field for merge snapshots**

A tuple containing 2 elements:

- resource type equivalent to `<resource-type>` of the target entity.
- resource ID euivalen to `<resource-id>` of the target entity.


## Unpaged search result
**Description**

A result of search operation that doesn't involve paging.

**Structure**

```json5
{
    "results": [
        <resource>,
        <resource>,
        <resource>
    ]
}
```

**Field meaning**
- `<resource>`: any resource - which exactly depends on the API call. For
  details on this field, check the documentation for given API call.

## Paged search result
**Description**

A result of search operation that involves paging.

**Structure**

```json5
{
    "query":    <query>,  // same as in input
    "offset":   <offset>, // same as in input
    "limit":    <page-size>,
    "total":    <total-count>,
    "results": [
        <resource>,
        <resource>,
        <resource>
    ]
}
```

**Field meaning**
- `<query>`: the query passed in the original request that contains standard
  [search query](#search).
- `<offset>`: the record starting offset, passed in the original request.
- `<page-size>`: number of records on one page.
- `<total-count>`: how many resources were found. To get the page count, divide
  this number by `<page-size>`.
- `<resource>`: any resource - which exactly depends on the API call. For
  details on this field, check the documentation for given API call.


## Image search result
**Description**

A result of reverse image search operation.

**Structure**

```json5
{
    "exactPost": <exact-post>,
    "similarPosts": [
        {
            "distance": <distance>,
            "post": <similar-post>
        },
        {
            "distance": <distance>,
            "post": <similar-post>
        },
        ...
    ]
}
```

**Field meaning**
-  `exact-post`: a [post resource](#post) that is exact byte-to-byte duplicate
   of the input file. May be `null`.
- `<similar-post>`: a [post resource](#post) that isn't exact duplicate, but
   visually resembles the input file. Works only on images and animations, i.e.
   does not work for videos and Flash movies. For non-images and corrupted
   images, this list is empty.
- `<distance>`: distance from the original image (0..1). The lower this value
   is, the more similar the post is.

# Search

Search queries are built of tokens that are separated by spaces. Each token can
be of following form:

| Syntax            | Token type        | Description                                |
| ----------------- | ----------------- | ------------------------------------------ |
| `<value>`         | anonymous tokens  | basic filters                              |
| `<key>:<value>`   | named tokens      | advanced filters                           |
| `sort:<style>`    | sort style tokens | sort the results                           |
| `special:<value>` | special tokens    | filters usually tied to the logged in user |

Most of anonymous and named tokens support ranged and composite values that
take following form:

| `<value>` | Description                                           |
| --------- | ----------------------------------------------------- |
| `a,b,c`   | will show things that satisfy either `a`, `b` or `c`. |
| `1..`     | will show things that are equal to or greater than 1. |
| `..4`     | will show things that are equal to at most 4.         |
| `1..4`    | will show things that are equal to 1, 2, 3 or 4.      |

Ranged values can be also supplied by appending `-min` or `-max` to the key,
for example like this: `score-min:1`.

Date/time values can be of following form:

- `today`
- `yesterday`
- `<year>`
- `<year>-<month>`
- `<year>-<month>-<day>`

Some fields, such as user names, can take wildcards (`*`).

You can escape special characters such as `:` and `-` by prepending them with a
backslash: `\\`.

**Example**

Searching for posts with following query:

    sea -fav-count:8.. type:swf uploader:Pirate

will show flash files tagged as sea, that were liked by seven people at most,
uploaded by user Pirate.

Searching for posts with `re:zero` will show an error message about unknown
named token.

Searching for posts with `re\:zero` will show posts tagged with `re:zero`.
