# szurubooru_photos

A fork of [szurubooru](https://github.com/rr-/szurubooru) aimed at storing photos and videos taken with physical cameras.

The idea for this started in [this issue](https://github.com/rr-/szurubooru/issues/340). I wanted a self-hosted replacement for Google Photos. *If you're looking for one, also consider [Immich](https://github.com/immich-app/immich). (I'm not affiliated with the project at all, I just think it's really cool and will be a much better replacement for this when it comes out. In fact I plan to build a migration tool when it does.)

## Added Features

- **Date taken**
  - Automatically extracts the date and time when the photo was taken from its EXIF metadata.
  - Shown on the post sidebar
- **Camera**
  - Automatically extracts the make and model of the camera used to take the photo
  - Also shown on the post sidebar
  - Also works with some videos
- **EXIF orientation support**
  - Acknowledges EXIF orientation and generates thumbnails and post dimensions correctly
  - Images with orientations other than 1 now render properly

## Notices

- Date taken and Camera do not work with image formats that lack EXIF support (such as PNG) or have been stripped of EXIF metadata.
- The current migration does not regenerate post thumbnails or fixes post dimensions. This will be fixed in the next commit.
- This fork might be less stable than upstream, since the changes I've made have not been tested on as large of a scale.

## Installation

[See installation instructions.](doc/INSTALL.md)

More installation resources as well as related projects can be found on upstream's [GitHub project Wiki](https://github.com/rr-/szurubooru/wiki).

## Status and Plans

This fork has reached the level of functionality I need for my personal use. As such, there are missing features that might be useful to some:

- Mass deletion support (will also implement this upstream when I get to it)
- Tests (honestly, I don't even want to touch these)
- Proper post merging
- Updated documentation

I am also planning to rewrite this fork's [`server`](server/) in another language sometime in the future.
