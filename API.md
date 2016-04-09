`szurubooru` uses REST API for all operations.



## Table of contents

1. [General rules](#general-rules)

   - [Authentication](#authentication)
   - [Basic requests](#basic-requests)
   - [File uploads](#file-uploads)
   - [Error handling](#error-handling)

2. [API reference](#api-reference)

   - [Listing users](#listing-users)
   - [Creating user](#creating-user)
   - [Updating user](#updating-user)
   - [Getting user](#getting-user)
   - [Removing user](#removing-user)
   - [Password reset - step 1: mail request](#password-reset---step-2-confirmation)
   - [Password reset - step 2: confirmation](#password-reset---step-2-confirmation)

3. [Resources](#resources)

   - [User](#user)

4. [Search](#search)



## General rules

### Authentication

Authentication is achieved by means of [basic HTTP
auth](https://en.wikipedia.org/wiki/Basic_access_authentication). For this
reason, it is recommended to connect through HTTPS. There are no sessions, so
every privileged request must be authenticated. Available privileges depend on
the user's rank. The way how rank translates to privileges is defined in the
server's configuration.

It is recommended to add `?bump-login` GET parameter to the first request in a
client "session" (where the definition of a session is up to the client), so
that the user's last login time is kept up to date.

### Basic requests

Every request must use `Content-Type: application/json` and `Accept:
application/json`. An exception to this rule are requests that upload files.

### File uploads

Requests that upload files must use `multipart/form-data` encoding. JSON
metadata must then be included as field of name `metadata`, whereas files must
be included as separate fields with names specific to each request type.

### Error handling

All errors (except for unhandled fatal server errors) send relevant HTTP status
code together with JSON of following structure:

```json5
{
    "title": "Generic title of error message, e.g. 'Not found'",
    "description": "Detailed description of what went wrong, e.g. 'User `rr-` not found."
}
```



## API reference

Depending on the deployment, the URLs might be relative to some base path such
as `/api/`. Values denoted in diamond braces (`<like this>`) signify variable
data.


### Listing users
Request: `GET /users/?page=<page>&query=<query>`  
Output:
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
...where `<user>` is an [user resource](#user) and `query` contains standard
[search query](#search).
Errors: if privileges are too low.

Searches for users.

Available search named tokens:

| name              | ranged? | array? |
| ----------------- | ------- | ------ |
| (anonymous)       |         | ✓      |
| `name`            |         | ✓      |
| `creation-date`   | ✓       | ✓      |
| `creation-time`   | ✓       | ✓      |
| `last-login-date` | ✓       | ✓      |
| `last-login-time` | ✓       | ✓      |
| `login-date`      | ✓       | ✓      |
| `login-time`      | ✓       | ✓      |

Anonymous search tokens are equivalent to `name` token.

Available search orders:

- `random`
- `name`
- `creation-date`
- `creation-time`
- `last-login-date`
- `last-login-time`
- `login-date`
- `login-time`


### Creating user
Request: `POST /users`  
Input:
```json5
{
    "name": <user-name>,
    "password": <user-password>,
    "email": <email>
}
```
Output:
```json5
{
    "user": <user>
}
```
...where `<user>` is an [user resource](#user).  
Errors: if such user already exists (names are case insensitive), or either of
user name, password and email are invalid, or privileges are too low.

Creates a new user using specified parameters. Names and passwords must match
`user_name_regex` and `password_regex` from server's configuration,
respectively. Email address is optional. If the user happens to be the first
user ever created, they're granted highest available rank, becoming an
administrator. Subsequent users will be given the rank indicated by
`default_rank` in the server's configuration.


### Updating user
Request: `PUT /user/<name>`  
Input:
```json5
{
    "name": <user-name>,
    "password": <user-password>,
    "email": <email>,
    "rank": <rank>,
    "avatar_style": <avatar-style>
}
```
Files: `avatar` - the content of the new avatar.  
Output:
```json5
{
    "user": <user>
}
```
...where `<user>` is an [user resource](#user).  
Errors: if the user does not exist, or the user with new name already exists
(names are case insensitive), or either of user name, password, email or rank
are invalid, or the user is trying to update their or someone else's rank to
higher than their own, or privileges are too low, or avatar is missing for
manual avatar style.

Updates an existing user using specified parameters. Names and passwords must
match `user_name_regex` and `password_regex` from server's configuration,
respectively. All fields are optional - update concerns only provided fields.
To update last login time, see [authentication](#authentication). Avatar style
can be either `gravatar` or `manual`. `manual` avatar style requires client to
pass also `avatar` file - see [file uploads](#file-uploads) for details.


### Getting user
Request: `GET /user/<name>`  
Output:
```json5
{
    "user": <user>
}
```
...where `<user>` is an [user resource](#user).  
Errors: if the user does not exist or privileges are too low.

Retrieves information about an existing user.


### Removing user
Request: `DELETE /user/<name>`  
Output:
```json5
{}
```
Errors: if the user does not exist or privileges are too low.

Deletes existing user.


### Password reset - step 1: mail request
Request: `GET /password-reset/<email-or-name>`  
Output:
```
{}
````
Errors: if the user does not exist, or they haven't provided an email address.

Sends a confirmation email to given user. The email contains link containing a
token. The token cannot be guessed, thus using such link proves that the person
who requested to reset the password, also owns the mailbox, which is a strong
indication they are the rightful owner of the account.


### Password reset - step 2: confirmation
Request: `POST /password-reset/<email-or-name>`  
Input:
```json5
{
    "token": <token-from-email>
}
```
Output:
```json5
{
    "password": <new-password>
}
```
Errors: if the token is missing, the token is invalid or the user does not
exist.

Generates a new password for given user. Password is sent as plain-text, so it
is recommended to connect through HTTPS.



## Resources

### User

```json5
{
    "id":            2,
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

## Search

Nomenclature:

- Tokens - search terms inside a query, separated by white space.
- Anonymous tokens - tokens of form `value`, used to filter the search results.
- Named tokens - tokens of form `key:value`, used to filter the search results.
- Special tokens - tokens of form `special:value`, used to filter the search results.
- Order tokens - tokens of form `order:value`, used to sort the search results.

Features:

- Most of tokens can be negated like so: `-token`. For order token it flips the
  sort direction.
- Some tokens support multiple values like so: `3,4,5`.
- Some tokens support ranges like so: `100..`, `..200`, `100..200`.
- Date token values can contain following values: `today`, `yesterday`,
  `<year>`, `<year>-<month>`, `<year>-<month>-<day>`.
- Order token values can be suffixed with `,asc` or `,desc`.

Example how it works:

    haruhi -kyon fav-count:3.. order:fav-count,desc -special:liked
