Prerequisites
-------------

In order to run `szurubooru`, you need to have installed following software:

- `Apache` 2.4+
    - `mod_rewrite`
    - `mod_mime_magic` (recommended)
- `PHP` 5.6.0+
    - `pdo_mysql`
    - `imagick` or `gd`
- `composer` (`PHP` package manager)
- `npm` (`node.js` package manager)

Optional software:

- `dump-gnash`, `swfrender` or `ffmpeg` for Flash thumbnails
- `ffmpegthumbnailer` or `ffmpeg` for video thumbnails



Cloning the repository
----------------------

Download the repository somewhere you will it run from, or better yet, clone it
with `git`:

    cd /srv/www/
    git clone https://github.com/rr-/szurubooru booru-test



Fetching dependencies
---------------------

To fetch dependencies that `szurubooru` needs in order to run, enter following
commands in the terminal:

    composer update
    npm update



Running `grunt` tasks
---------------------

`szurubooru` uses `grunt` to run tasks like database upgrades and tests. In
order to use `grunt` from the terminal, you can use:

    node_modules/grunt-cli/bin/grunt [TASK]

But since it's inconvenient, you can install it globally by running as
administrator:

    npm install -g grunt-cli

This will add `grunt` to your PATH, making things much more human-friendly.

    grunt [TASK]



Enabling required modules in `PHP`
----------------------------------

Enable required modules in `php.ini` (or other configuration file, depending on
your setup):

    ;Linux
    extension=pdo_mysql.so

    ;Windows
    extension=php_pdo_mysql.dll

In order to draw thumbnails, `szurubooru` needs either `Imagick` or `gd2`:

    ;Linux
    extension=imagick.so
    extension=gd.so

    ;Windows
    extension=php_imagick.dll
    extension=php_gd2.dll



Creating virtual server in Apache
---------------------------------

In order to make `szurubooru` visible in your browser, you need to create a
virtual server. This guide focuses on `Apache` web server. Note that although
it should be also possible to host `szurubooru` with `nginx`, you'd need to
manually translate the rules inside `public_html/.htaccess` into `nginx`
configuration.

Creating virtual server for `Apache` comes with no surprises, basically all you
need is the most basic configuration:

    <VirtualHost *:80>
        ServerName example.com
        DocumentRoot /path/to/szurubooru/public_html/
    </VirtualHost>

`ServerName` specifies the domain under which `szurubooru` will be hosted.
`DocumentRoot` should point to the `public_html/` directory.

Some environments / configurations require extra steps to make things work - in
case you experience any problems, please consult the troubleshooting section
later in this file.



Enabling required modules in Apache
-----------------------------------

Enable required modules in `httpd.conf` (or other configuration file, depending
on your setup):

    LoadModule rewrite_module mod_rewrite.so ;Linux
    LoadModule rewrite_module modules/mod_rewrite.so ;Windows

Enable `PHP` support:

    LoadModule php5_module /usr/lib/apache2/modules/libphp5.so ;Linux
    LoadModule php5_module /path/to/php/php5apache2_4.dll ;Windows
    AddType application/x-httpd-php .php
    PHPIniDir /path/to/php/

Enable MIME auto-detection (not required, but recommended - `szurubooru`
doesn't use file extensions, and reporting correct `Content-Type` to browser is
always a good thing):

    ;Linux
    LoadModule mime_magic_module mod_mime_magic.so
    <IfModule mod_mime_magic.c>
        MIMEMagicFile /etc/apache2/magic
    </IfModule>

    ;Windows
    LoadModule mime_magic_module modules/mod_mime_magic.so
    <IfModule mod_mime_magic.c>
        MIMEMagicFile conf/magic
    </IfModule>



Overwriting configuration
-------------------------

Everything that can be configured is stored in `data/config.ini` file. In order
to make changes there, copy the file and name it `local.ini` and place it in
`data/` directory as well. Make sure you don't edit the `data/config.ini` file
itself, especially if you want to contribute.



Setting up the database
-----------------------

Before running `szurubooru` for first time, you need to set up the database.
`szurubooru` uses MySQL, so let's fire `mysql` and type following:

    create user 'maria' identified by 'arkadia';
    create database booru_test;
    grant all privileges on *.* to 'maria'@'%' with grant option;

Then you need to provide the above credentials in the configuration files as
described in the previous section. Example `local.ini` file:

    [database]
    dsn = mysql:dbname=booru_test
    user = maria
    password = arkadia

After that, upgrade the database using following command:

    grunt upgrade

This should be also executed every time database schema changes.



Compiling assets
----------------

Generally HTML templates, CSS stylesheets and JS scripts are scattered over
many files. This is desirable for development environment, but creates large
network overhead for production environment. In order to minify the files into
smallest possible packages, run following command:

    grunt build

This should create `public_html/app.min.js`, `public_html/app.min.css` and
`public_html/app.min.html`. `.htaccess` is configured so that if these files
exist, it will load them instead of development environment. To delete these
conveniently, you can run:

    grunt clean

If, for any reason, you do not wish to minify the resources, you should at
least copy the dependencies fetched before to the `public_html/` directory with
following:

    grunt copy



Creating administrator account
------------------------------

By now, you should be able to view `szurubooru` in the browser. Registering
administrator account is simple - the first user to create an account
automatically becomes administrator and doesn't need e-mail activation.



Troubleshooting
---------------

 1. Problems with `Apache` virtual servers

    After reloading `Apache` configuration, if you find yourself unable to
    connect to the server, make sure that connections are open, for example,
    like this:

        <Directory /path/to/szurubooru/public_html/>
            Require all granted
        </Directory>

    (Note that `Apache` versions prior to 2.4 used `Allow from all` directive.)

    Additionally, in order to access the virtual host from your machine, make
    sure the domain name `example.com` supplied in `<VirtualHost/>` section is
    included in your `hosts` file (usually `/etc/hosts` on Linux and
    `C:/windows/system32/drivers/etc/hosts` on Windows).

    If the site doesn't work for you, make sure `Apache` can parse `.htaccess`
    files. If it can't, you need to set `AllowOverride` option to `yes`, for
    example by putting following snippet inside the `<VirtualHost/>` section:

        <Directory /path/to/szurubooru/public_html/>
            AllowOverride All
        </Directory>

 2. Problems with `PHP` modules or registration

    Make sure your `php.ini` path is correct. Make sure all the modules are
    actually loaded by inspecting results of `phpinfo()` call - create small
    file containing:

        <?php phpinfo(); ?>

    Then, run it in your browser and inspect the output, looking for missing
    modules that were supposed to be loaded.
