# szurubooru

Szurubooru is an image board engine inspired by services such as Danbooru,
Gelbooru and Moebooru dedicated for small and medium communities. Its name [has
its roots in Polish language and has onomatopeic meaning of scraping or
scrubbing](https://sjp.pwn.pl/sjp/;2527372). It is pronounced as *shoorubooru*.

## Features

- Post content: images (JPG, PNG, GIF, animated GIF), videos (MP4, WEBM), Flash animations
- Ability to retrieve web video content using [yt-dlp](https://github.com/yt-dlp/yt-dlp)
- Post comments
- Post notes / annotations, including arbitrary polygons
- Rich JSON REST API ([see documentation](doc/API.md))
- Token based authentication for clients
- Rich search system
- Rich privilege system
- Autocomplete in search and while editing tags
- Tag categories
- Tag suggestions
- Tag implications (adding a tag automatically adds another)
- Tag aliases
- Pools and pool categories
- Duplicate detection
- Post rating and favoriting; comment rating
- Polished UI
- Browser configurable endless paging
- Browser configurable backdrop grid for transparent images

## Installation

It is recommended that you use Docker for deployment.
[See installation instructions.](doc/INSTALL.md)

More installation resources, as well as related projects can be found on the
[GitHub project Wiki](https://github.com/rr-/szurubooru/wiki)

## Screenshots

Post list:

![20160908_180032_fsk](https://cloud.githubusercontent.com/assets/1045476/18356730/3f1123d6-75ee-11e6-85dd-88a7615243a0.png)

Post view:

![20160908_180429_lmp](https://cloud.githubusercontent.com/assets/1045476/18356731/3f1566ee-75ee-11e6-9594-e86ca7347b0f.png)

## License

[GPLv3](LICENSE.md).
