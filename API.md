`szurubooru` uses REST API for all operations.



# Table of contents

1. [General rules](#general-rules)

   - [Authentication](#authentication)
   - [Basic requests](#basic-requests)
   - [File uploads](#file-uploads)
   - [Error handling](#error-handling)

2. [API reference](#api-reference)

   - [Listing tags](#listing-tags)
   - [Creating tag](#creating-tag)
   - [Updating tag](#updating-tag)
   - [Getting tag](#getting-tag)
   - [Deleting tag](#deleting-tag)
   - [Listing users](#listing-users)
   - [Creating user](#creating-user)
   - [Updating user](#updating-user)
   - [Getting user](#getting-user)
   - [Deleting user](#deleting-user)
   - [Password reset - step 1: mail request](#password-reset---step-2-confirmation)
   - [Password reset - step 2: confirmation](#password-reset---step-2-confirmation)

3. [Resources](#resources)

   - [User](#user)
   - [Tag](#tag)

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


## Listing tags
- **Request**

    `GET /tags/?page=<page>&pageSize=<page-size>&query=<query>`

- **Output**

    ```json5
    {
        "query": "haruhi",
        "tags": [
            <tag>,
            <tag>,
            <tag>,
            <tag>,
            <tag>
        ],
        "page": 1,
        "pageSize": 5,
        "total": 7
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

    **Order tokens**

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
        "implications": [<name1>, <name2>, ...],
        "suggestions":  [<name1>, <name2>, ...]
    }
    ```

- **Output**

    ```json5
    {
        "tag": <tag>
    }
    ```
    ...where `<tag>` is a [tag resource](#tag).

- **Errors**

    - any name is used by an existing tag (names are case insensitive)
    - any name, implication or suggestion has invalid name
    - category is invalid
    - no name was specified
    - implications or suggestions contain any item from names (e.g. there's a
      shallow cyclic dependency)
    - privileges are too low

- **Description**

    Creates a new tag using specified parameters. Names, suggestions and
    implications must match `tag_name_regex` from server's configuration.
    Category must be one of `tag_categories` from server's configuration.
    If specified implied tags or suggested tags do not exist yet, they will
    be automatically created. Tags created automatically have no implications,
    no suggestions, one name and their category is set to the first item of
    `tag_categories` from server's configuration.


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
        "tag": <tag>
    }
    ```
    ...where `<tag>` is a [tag resource](#tag).

- **Errors**

    - any name is used by an existing tag (names are case insensitive)
    - any name, implication or suggestion has invalid name
    - category is invalid
    - no name was specified
    - implications or suggestions contain any item from names (e.g. there's a
      shallow cyclic dependency)
    - privileges are too low

- **Description**

    Updates an existing tag using specified parameters. Names, suggestions and
    implications must match `tag_name_regex` from server's configuration.
    Category must be one of `tag_categories` from server's configuration.
    If specified implied tags or suggested tags do not exist yet, they will
    be automatically created. Tags created automatically have no implications,
    no suggestions, one name and their category is set to the first item of
    `tag_categories` from server's configuration. All fields are optional -
    update concerns only provided fields.


## Getting tag
- **Request**

    `GET /tag/<name>`

- **Output**

    ```json5
    {
        "tag": <tag>
    }
    ```
    ...where `<tag>` is a [tag resource](#tag).

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

    Deletes existing tag.


## Listing users
- **Request**

    `GET /users/?page=<page>&pageSize=<page-size>&query=<query>`

- **Output**

    ```json5
    {
        "query": "rr-",
        "users": [
            <user>,
            <user>,
            <user>,
            <user>,
            <user>
        ],
        "page": 1,
        "pageSize": 5,
        "total": 7
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

    **Order tokens**

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



# Resources

## User

```json5
{
    "name":          "rr-",
    "email":         "rr-@sakuya.pl",    // available only if the request is authenticated by the same user
    "rank":          "admin",            // controlled by server's configuration
    "rankName":      "Administrator",    // controlled by server's configuration
    "lastLoginTime": "2016-04-08T20:20:16.570517",
    "creationTime":  "2016-03-28T13:37:01.755461",
    "avatarStyle":   "gravatar",        // "gravatar" or "manual"
    "avatarUrl":     "http://gravatar.com/(...)"
}
```

## Tag

```json5
{
    "names":        ["tag1", "tag2", "tag3"],
    "category":     "plain", // one of values controlled by server's configuration
    "implications": ["implied-tag1", "implied-tag2", "implied-tag3"],
    "suggestions":  ["suggested-tag1", "suggested-tag2", "suggested-tag3"],
    "creationTime": "2016-03-28T13:37:01.755461",
    "lastEditTime": "2016-04-08T20:20:16.570517"
}
```



# Search

Search queries are built of tokens that are separated by spaces. Each token can
be of following form:

| Syntax            | Token type        | Description                                |
| ----------------- | ----------------- | ------------------------------------------ |
| `<value>`         | anonymous tokens  | basic filters                              |
| `<key>:<value>`   | named tokens      | advanced filters                           |
| `order:<style>`   | order tokens      | sort results                               |
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
