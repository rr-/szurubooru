![szurubooru](https://raw.githubusercontent.com/rr-/szurubooru/master/public_html/img/shrine.png)

szurubooru
==========

## What is it?

Szurubooru is a Danbooru-style board, a gallery where users can upload, browse,
tag and comment images, video clips and flash animations.

Its name have its roots in Polish language and has onomatopoeic meaning of
scraping or scrubbing. It is pronounced *"shoorubooru"* [ˌʃuruˈburu].

## Licensing

Please see the file named [`LICENSE`](https://github.com/rr-/szurubooru/blob/master/LICENSE).

## Installation

Please see the file named [`INSTALL.md`](https://github.com/rr-/szurubooru/blob/master/INSTALL.md).

## Bugs and feature requests

All bugs and suggestions should be reported as issues on the [Github
repository page](https://github.com/rr-/szurubooru/issues). When reporting,
please do following:

 1. Search for existing issues for possible duplicates. If something is related
    to your problem, comment on that issue instead of opening a new one.
 2. If you found an issue and the issue is closed, feel free to reopen it.
 3. If you're reporting a bug, create an isolated and reproducible scenario.
 4. If you're filing a feature request, provide examples - what might be obvious
    to you, might not be so obvious to the developers.

## Contributing the code

Here are some guidelines on how to contribute:

 - Keep your changes compact.
 - Respect coding standards - be consistent with existing code base.
 - Watch your whitespace - don't leave any characters at the end of the lines.
 - Always run tests before pushing.
 - Before starting, see [`INSTALL.md`](https://github.com/rr-/szurubooru/blob/master/INSTALL.md).
 - Use `grunt` to do automatic tasks like minifying Javascript files or running
   tests. Run `grunt --help` to see full list of available tasks.

## API

Szurubooru from version 0.9+ uses REST API. Currently there is no formal
documentation; source code behind REST layer lies in `src/Controllers/`
directory. In order to use the API, bear in mind that you need to:

 1. Have actual user account on the server to do most things (depending on
    privileges).
 2. Authenticate your requests:
     1. Send user credentials to `/auth`. You'll receive authentication token in
        return.
     2. Send this token in X-Authorization-Token header on subsequent requests.

Developers reserve right to change API at any time with neither prior notice
nor keeping backwards compatibility.
