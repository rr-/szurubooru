# szurubooru

A fork of [szurubooru](https://github.com/rr-/szurubooru) aimed at storing photos and videos taken with physical cameras.

The idea for this started in [this issue](https://github.com/rr-/szurubooru/issues/340). I wanted a self-hosted replacement for Google Photos.

## Added Features

- **Date taken**
  - Automatically extracts the date and time that the photo was taken on from its EXIF metadata.
  - Date taken is shown on the post sidebar
- **Camera**
  - Automatically extracts the make and model of the camera used to take the photo
  - Also works with some videos
- **EXIF orientation support**
  - Acknowledges EXIF orientation and generates thumbnails and post dimensions correctly
  - Images with orientations other than 1 now render properly

**Please note:** Date taken and Camera do not work with image formats that lack EXIF support (such as PNG) or have been stripped of EXIF metadata.

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
