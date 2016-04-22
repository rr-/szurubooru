`szurubooru` uses REST API for all operations.



# Table of contents

1. [General rules](#general-rules)

   - [Authentication](#authentication)
   - [Basic requests](#basic-requests)
   - [File uploads](#file-uploads)
   - [Error handling](#error-handling)

2. [API reference](#api-reference)

    - Tag categories
        - [Listing tag categories](#listing-tag-categories)
        - [Creating tag category](#creating-tag-category)
        - [Updating tag category](#updating-tag-category)
        - [Getting tag category](#getting-tag-category)
        - [Deleting tag category](#deleting-tag-category)
    - Tags
        - [Listing tags](#listing-tags)
        - [Creating tag](#creating-tag)
        - [Updating tag](#updating-tag)
        - [Getting tag](#getting-tag)
        - [Deleting tag](#deleting-tag)
        - [Merging tags](#merging-tags)
        - [Listing tag siblings](#listing-tag-siblings)
    - Users
        - [Listing users](#listing-users)
        - [Creating user](#creating-user)
        - [Updating user](#updating-user)
        - [Getting user](#getting-user)
        - [Deleting user](#deleting-user)
    - Password reset
        - [Password reset - step 1: mail request](#password-reset---step-2-confirmation)
        - [Password reset - step 2: confirmation](#password-reset---step-2-confirmation)
    - Snapshots
        - [Listing snapshots](#listing-snapshots)

3. [Resources](#resources)

   - [User](#user)
   - [Tag category](#tag-category)
   - [Tag](#tag)
   - [Snapshot](#snapshot)

4. [Search](#search)



# General rules

## Authentication

Authentication is achieved by means of [basic HTTP
auth](https://en.wikipedia.org/wiki/Basic_access_authentication). For this
reason, it is recommended to connect through HTTPS. There are no sessions, so
every privileged request must be authenticated. Available privileges depend on
the user's rank. The way how rank translates to privileges is defined in the
server's configuration.

It is recommended to add `?bump-login` GET parameter to the first request in a
client "session" (where the definition of a session is up to the client), so
that the user's last login time is kept up to date.

## Basic requests

Every request must use `Content-Type: application/json` and `Accept:
application/json`. An exception to this rule are requests that upload files.

## File uploads

Requests that upload files must use `multipart/form-data` encoding. JSON
metadata must then be included as field of name `metadata`, whereas files must
be included as separate fields with names specific to each request type.

## Error handling

All errors (except for unhandled fatal server errors) send relevant HTTP status
code together with JSON of following structure:

```json5
{
    "title": "Generic title of error message, e.g. 'Not found'",
    "description": "Detailed description of what went wrong, e.g. 'User `rr-` not found."
}
```



# API reference

Depending on the deployment, the URLs might be relative to some base path such
as `/api/`. Values denoted with diamond braces (`<like this>`) signify variable
data.


## Listing tag categories
- **Request**

    `GET /tag-categories`

- **Output**

    ```json5
    {
        "tagCategories": [
            <tag-category>,
            <tag-category>,
            <tag-category>
        ]
    }
    ```
    ...where `<tag-category>` is a [tag category resource](#tag-category).

- **Errors**

    - privileges are too low

- **Description**

    Lists all tag categories. Doesn't use paging.

    **Note**: independently, the server exports current tag category list
    snapshots to the data directory under `tags.json` name. Its purpose is to
    reduce the trips frontend needs to make when doing autocompletion, and ease
    caching. The data directory and its URL are controlled with `data_dir` and
    `data_url` variables in server's configuration.


## Creating tag category
- **Request**

    `POST /tag-categories`

- **Input**

    ```json5
    {
        "name":  <name>,
        "color": <color>
    }
    ```

- **Output**

    ```json5
    {
        "tagCategory": <tag-category>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag-category>` is a [tag category resource](#tag-category), and
    `snapshots` contain its earlier versions.

- **Errors**

    - the name is used by an existing tag category (names are case insensitive)
    - the name is invalid or missing
    - the color is invalid or missing
    - privileges are too low

- **Description**

    Creates a new tag category using specified parameters. Name must match
    `tag_category_name_regex` from server's configuration.


## Updating tag category
- **Request**

    `PUT /tag-category/<name>`

- **Input**

    ```json5
    {
        "name":  <name>,    // optional
        "color": <color>,   // optional
    }
    ```

- **Output**

    ```json5
    {
        "tagCategory": <tag-category>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag-category>` is a [tag category resource](#tag-category), and
    `snapshots` contain its earlier versions.

- **Errors**

    - the tag category does not exist
    - the name is used by an existing tag category (names are case insensitive)
    - the name is invalid
    - the color is invalid
    - privileges are too low

- **Description**

    Updates an existing tag category using specified parameters. Name must
    match `tag_category_name_regex` from server's configuration. All fields are
    optional - update concerns only provided fields.


## Getting tag category
- **Request**

    `GET /tag-category/<name>`

- **Output**

    ```json5
    {
        "tagCategory": <tag-category>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag-category>` is a [tag category resource](#tag-category), and
    `snapshots` contain its earlier versions.

- **Errors**

    - the tag category does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing tag category.


## Deleting tag category
- **Request**

    `DELETE /tag-category/<name>`

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the tag category does not exist
    - the tag category is used by some tags
    - the tag category is the last tag category available
    - privileges are too low

- **Description**

    Deletes existing tag category. The tag category to be deleted must have no
    usages.


## Listing tags
- **Request**

    `GET /tags/?page=<page>&pageSize=<page-size>&query=<query>`

- **Output**

    ```json5
    {
        "query":    <query>, // same as in input
        "page":     <page>,  // same as in input
        "pageSize": <page-size>,
        "total":    <total-count>,
        "tags": [
            <tag>,
            <tag>,
            <tag>
        ]
    }
    ```
    ...where `<tag>` is a [tag resource](#tag) and `query` contains standard
    [search query](#search).

- **Errors**

    - privileges are too low

- **Description**

    Searches for tags.

    **Note**: independently, the server exports current tag list snapshots to
    the data directory under `tags.json` name. Its purpose is to reduce the
    trips frontend needs to make when doing autocompletion, and ease caching.
    The data directory and its URL are controlled with `data_dir` and
    `data_url` variables in server's configuration.

    **Anonymous tokens**

    Same as `name` token.

    **Named tokens**

    | `<value>`           | Description                           |
    | ------------------- | ------------------------------------- |
    | `name`              | having given name (accepts wildcards) |
    | `category`          | having given category                 |
    | `creation-date`     | created at given date                 |
    | `creation-time`     | alias of `creation-date`              |
    | `last-edit-date`    | edited at given date                  |
    | `last-edit-time`    | alias of `last-edit-date`             |
    | `edit-date`         | alias of `last-edit-date`             |
    | `edit-time`         | alias of `last-edit-date`             |
    | `usages`            | used in given number of posts         |
    | `usage-count`       | alias of `usages`                     |
    | `post-count`        | alias of `usages`                     |
    | `suggestion-count`  | with given number of suggestions      |
    | `implication-count` | with given number of implications     |

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
        "implications": [<name1>, <name2>, ...],    // optional
        "suggestions":  [<name1>, <name2>, ...]     // optional
    }
    ```

- **Output**

    ```json5
    {
        "tag": <tag>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag>` is a [tag resource](#tag), and `snapshots` contain its
    earlier versions.

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

    `PUT /tags/<name>`

- **Input**

    ```json5
    {
        "names":        [<name1>, <name2>, ...],    // optional
        "category":     <category>,                 // optional
        "implications": [<name1>, <name2>, ...],    // optional
        "suggestions":  [<name1>, <name2>, ...]     // optional
    }
    ```

- **Output**

    ```json5
    {
        "tag": <tag>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag>` is a [tag resource](#tag), and `snapshots` contain its
    earlier versions.

- **Errors**

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
    their category is set to the first tag category found. All fields are
    optional - update concerns only provided fields.


## Getting tag
- **Request**

    `GET /tag/<name>`

- **Output**

    ```json5
    {
        "tag": <tag>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag>` is a [tag resource](#tag), and `snapshots` contain its
    earlier versions.

- **Errors**

    - the tag does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing tag.


## Deleting tag
- **Request**

    `DELETE /tag/<name>`

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the tag does not exist
    - the tag is used by some posts
    - privileges are too low

- **Description**

    Deletes existing tag. The tag to be deleted must have no usages.


## Merging tags
- **Request**

    `POST /tag-merge/`

- **Input**

    ```json5
    {
        "remove":   <source-tag-name>,
        "merge-to": <target-tag-name>
    }
    ```

- **Output**

    ```json5
    {
        "tag": <tag>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<tag>` is the target [tag resource](#tag), and `snapshots`
    contain its earlier versions.

- **Errors**

    - the source or target tag does not exist
    - the source tag is the same as the target tag
    - privileges are too low

- **Description**

    Removes source tag and merges all of its usages to the target tag. Source
    tag properties such as category, tag relations etc. do not get transferred
    and are discarded. The target tag effectively remains unchanged with the
    exception of the set of posts it's used in.


## Listing tag siblings
- **Request**

    `GET /tag-siblings/<name>`

- **Output**

    ```json5
    {
        "siblings": [
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


## Listing users
- **Request**

    `GET /users/?page=<page>&pageSize=<page-size>&query=<query>`

- **Output**

    ```json5
    {
        "query":    <query>, // same as in input
        "page":     <page>,  // same as in input
        "pageSize": <page-size>,
        "total":    <total-count>,
        "users": [
            <user>,
            <user>,
            <user>
        ]
    }
    ```
    ...where `<user>` is a [user resource](#user) and `query` contains standard
    [search query](#search).

- **Errors**

    - privileges are too low

- **Description**

    Searches for users.

    **Anonymous tokens**

    Same as `name` token.

    **Named tokens**

    | `<value>`         | Description                                     |
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
        "name": <user-name>,
        "password": <user-password>,
        "email": <email>,               // optional
        "rank": <rank>,                 // optional
        "avatarStyle": <avatar-style>   // optional
    }
    ```

- **Files**

    - `avatar` - the content of the new avatar (optional).

- **Output**

    ```json5
    {
        "user": <user>
    }
    ```
    ...where `<user>` is a [user resource](#user).

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
    ever created, they're granted highest available rank, becoming an
    administrator, whereas subsequent users will be given the rank indicated by
    `default_rank` in the server's configuration.


## Updating user
- **Request**

    `PUT /user/<name>`

- **Input**

    ```json5
    {
        "name": <user-name>,            // optional
        "password": <user-password>,    // optional
        "email": <email>,               // optional
        "rank": <rank>,                 // optional
        "avatarStyle": <avatar-style>   // optional
    }
    ```

- **Files**

    - `avatar` - the content of the new avatar (optional).

- **Output**

    ```json5
    {
        "user": <user>
    }
    ```
    ...where `<user>` is a [user resource](#user).

- **Errors**

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
    file - see [file uploads](#file-uploads) for details.


## Getting user
- **Request**

    `GET /user/<name>`

- **Output**

    ```json5
    {
        "user": <user>
    }
    ```
    ...where `<user>` is a [user resource](#user).

- **Errors**

    - the user does not exist
    - privileges are too low

- **Description**

    Retrieves information about an existing user.


## Deleting user
- **Request**

    `DELETE /user/<name>`

- **Output**

    ```json5
    {}
    ```

- **Errors**

    - the user does not exist
    - privileges are too low

- **Description**

    Deletes existing user.


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

    `GET /snapshots/?page=<page>&pageSize=<page-size>&query=<query>`

- **Output**

    ```json5
    {
        "query":    <query>, // same as in input
        "page":     <page>,  // same as in input
        "pageSize": <page-size>,
        "total":    <total-count>,
        "snapshots": [
            <snapshot>,
            <snapshot>,
            <snapshot>
        ]
    }
    ```
    ...where `<snapshot>` is a [snapshot resource](#snapshot) and `query`
    contains standard [search query](#search).

- **Errors**

    - privileges are too low

- **Description**

    Lists recent resource snapshots.

    **Anonymous tokens**

    Not supported.

    **Named tokens**

    | `<value>`         | Description                                   |
    | ----------------- | --------------------------------------------- |
    | `type`            | involving given resource type                 |
    | `id`              | involving given resource id                   |
    | `date`            | created at given date                         |
    | `time`            | alias of `date`                               |
    | `operation`       | `changed`, `created` or `deleted`             |
    | `user`            | name of the user that created given snapshot  |

    **Sort style tokens**

    None. The snapshots are always sorted by creation time.

    **Special tokens**

    None.



# Resources

## User
**Description**

A single user.

**Structure**

```json5
{
    "name":          <name>,
    "email":         <email>,
    "rank":          <rank>,
    "rankName":      <rank-name>,
    "lastLoginTime": <last-login-time>,
    "creationTime":  <creation-time>,
    "avatarStyle":   <avatar-style>,
    "avatarUrl":     <avatar-url>
}
```

**Field meaning**
- `<name>`: the user name.
- `<email>`: the user email. It is available only if the request is
  authenticated by the same user.
- `<rank>`: the user rank, which effectively affects their privileges. The
  available ranks are stored in the server configuration.
- `<rank-name>`: the text representation of user's rank. Like `<rank>`, the
  possible values depend on the server configuration.
- `<last-login-time>`: the last login time, formatted as per RFC 3339.
- `<creation-time>`: the user registration time, formatted as per RFC 3339.
- `<avatarStyle>`: how to render the user avatar.

    Possible values:

    - `"gravatar"`: the user uses Gravatar.
    - `"manual"`: the user has uploaded a picture manually.

- `<avatarUrl>`: the URL to the avatar.

## Tag category
**Description**

A single tag category. The primary purpose of tag categories is to distinguish
certain tag types (such as characters, media type etc.), which improves user
experience.

**Structure**

```json5
{
    "name":  <name>,
    "color": <color>
}
```

**Field meaning**

- `<name>`: the category name.
- `<color>`: the category color.

## Tag
**Description**

A single tag. Tags are used to let users search for posts.

**Structure**

```json5
{
    "names":        <names>,
    "category":     <category>,
    "implications": <implications>,
    "suggestions":  <suggestions>,
    "creationTime": <creation-time>,
    "lastEditTime": <last-edit-time>
}
```

**Field meaning**

- `<names>`: a list of tag names (aliases). Tagging a post with any name will
  automatically assign the first name from this list.
- `<category>`: the name of the category the given tag belongs to.
- `<implications>`: a list of implied tag names. Implied tags are automatically
  appended by the web client on usage.
- `<suggestions>`: a list of suggested tag names. Suggested tags are shown to
  the user by the web client on usage.
- `<creation-time>`: time the tag was created, formatted as per RFC 3339.
- `<creation-time>`: time the tag was edited, formatted as per RFC 3339.

## Snapshot
**Description**

A snapshot is a version of a database resource.

**Structure**

```json5
{
    "operation":    <operation>,
    "type":         <resource-type>
    "id":           <resource-id>,
    "user":         <user-name>,
    "data":         <data>,
    "earlier-data": <earlier-data>,
    "time":         <time>
}
```

**Field meaning**

- `<operation>`: what happened to the resource.

    The value can be either of values below:

    - `"created"` - the resource has been created
    - `"modified"` - the resource has been modified
    - `"deleted"` - the resource has been deleted

- `<resource-type>` and `<resource-id>`: the resource that was changed.

    The values are correlated as per table below:

    | `<resource-type>` | `<resource-id>`                 |
    | ----------------- | ------------------------------- |
    | `"tag"`           | first tag name at given time    |
    | `"tag_category"`  | tag category name at given time |
    | `"post"`          | post ID                         |

- `<user-name>`: name of the user who has made the change.

- `<data>`: the snapshot data.

    The value can be either of structures below:

    - Tag category snapshot data (`<resource-type> = "tag"`)

        *Example*

        ```json5
        {
            "name":  "character",
            "color": "#FF0000"
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

- `<earlier-data>`: `<data>` field from the last snapshot of the same resource.
  This allows the client to create visual diffs for any given snapshot without
  the need to know any other snapshots for a given resource.

- `<time>`: when the snapshot was created (i.e. when the resource was changed),
  formatted as per RFC 3339.


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

**Example**

Searching for posts with following query:

    sea -fav-count:8.. type:swf uploader:Pirate

will show flash files tagged as sea, that were liked by seven people at most,
uploaded by user Pirate.
