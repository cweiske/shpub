**************************************
shpub - micropub client for your shell
**************************************
Command line `micropub <https://micropub.net/>`_ client written in PHP.


.. contents::

========
Download
========
shpub is released as self-contained ``.phar`` file that includes
all dependencies.

.. LATESTRELEASE

See `shpub downloads page <http://cweiske.de/shpub-download.htm>`_
for all released versions.


Dependencies
============
When using the git version, you need to have the following dependencies
installed on your system:

- PHP 5.4+
- PEAR's `Console_CommandLine <http://pear.php.net/package/Console_CommandLine>`_
- PEAR's `HTTP_Request2 <http://pear.php.net/package/HTTP_Request2>`_
- PEAR's `MIME_Type <http://pear.php.net/package/MIME_Type>`_
- PEAR's `NET_URL2 <http://pear.php.net/package/Net_URL2>`_


=============
Initial setup
=============
::

    $ ./bin/shpub.php connect http://mywebsite

Different user::

    $ ./bin/shpub.php connect http://mywebsite http://mywebsite/user

If you pass a third parameter, then it will be the name of the connection.
You can select a specific server/connection with ``-s`` on all commands.


List configured servers/connections
===================================
::

    $ ./bin/shpub.php server
    rr
    test
    anoweco.bogo
    local2

Also try ``server -v`` which lists server and user URLs.


=============
Post creation
=============
shpub has support for the following post types:

- `article <http://indieweb.org/article>`_
- `bookmark <http://indieweb.org/bookmark>`_
- `like <http://indieweb.org/like>`_
- `note <http://indieweb.org/note>`_
- `reply <http://indieweb.org/reply>`_
- `repost <http://indieweb.org/repost>`_
- `rsvp <http://indieweb.org/rsvp>`_

``shpub`` sends data form-encoded by default.
To send JSON requests, use the ``--json`` option.


Create a like
=============
::

    $ ./bin/shpub.php like http://example.org/
    Like created at server
    http://anoweco.bogo/comment/23.htm

Create a reply
==============
::

    $ ./bin/shpub.php reply http://example.org/ "Hey, cool!"
    Reply created at server
    http://anoweco.bogo/comment/42.htm


Create a note
=============
A normal note::

    $ ./bin/shpub.php note "oh this is cool!"
    Note created at server
    http://known.bogo/2016/oh-this-is-cool.htm

Note with an image::

    $ ./bin/shpub.php note -f image.jpg "this is so cute"
    Note created at server
    http://known.bogo/2016/this-is-so-cute

You can use ``-f`` several times to upload multiple files.

URL image upload::

    $ ./bin/shpub.php note -f http://example.org/1.jpg "img url!"
    Note created at server
    http://known.bogo/2016/img-url

Load note content from a file::

    $ ./bin/shpub.php note - < /path/to/file.txt
    Note created at server
    http://known.bogo/2017/some-note


Custom post types
=================
You may create custom post types with the ``x`` command.
This is useful if your micropub endpoint supports additional types,
like known's ``annotation`` type (comments and likes for posts).

Create a comment to a known post::

    $ ./bin/shpub.php x annotation\
        -x url=http://known.bogo/2016/example-domain-1\
        -x type=reply\
        -x username=barryf\
        -x userurl=http://example.org/~barryf\
        -x userphoto=http://example.org/~barryf/avatar.jpg\
        -x content="There is a typo in paragraph 1. 'Fou' should be 'Foo'"


===============
Delete/Undelete
===============
You may delete and restore posts on micropub servers::

    $ ./bin/shpub.php delete http://known.bogo/2016/like

Restore a deleted post::

    $ ./bin/shpub.php undelete http://known.bogo/2016/like


=======
Updates
=======
Existing posts can be modified if the `server supports this`__::

    $ ./bin/shpub update --add category=foo category=bar\
                         --replace slug=differentslug\
                         --delete category=oldcat\
                         http://known.bogo/2016/post

__ https://indieweb.org/Micropub/Servers#Implementation_status


===================
Syndication targets
===================
You may list the syndication targets defined on the server::

    $ ./bin/shpub.php targets
    IndieNews
     https://news.indieweb.org/en

Then specify it when creating a post::

    $ ./bin/shpub.php article -x mp-syndicate-to=https://news.indieweb.org/en title text

============
File uploads
============
Most post types allow file uploads. Simply use ``-f``::

    $ ./bin/shpub.php note -f path/to/image.jpg "image test"
    Note created at server
    http://known.bogo/2016/image-test

The media endpoint is used automatically if the micropub endpoint has one.
To force shpub to directly upload the file and skip the media endpoint,
use the ``--direct-upload`` option::

    $ ./bin/shpub.php note --direct-upload -f path/to/image.jpg "direct upload"

Use the ``upload`` command to upload files to the media endpoint without
creating a post::

    $ ./bin/shpub.php upload /path/to/file.jpg /path/to/file2.jpg
    Uploaded file /path/to/file.jpg
    http://test.bogo/micropub-media-endpoint/1474362040.2941/file.jpg
    Uploaded file /path/to/file2.jpg
    http://test.bogo/micropub-media-endpoint/1474362040.3383/file2.jpg


=========
Debugging
=========
To debug ``shpub`` or your micropub endpoint, use the ``--debug`` option
to see ``curl`` command equivalents to the shpub HTTP requests::

    $ ./bin/shpub.php -s known -d note "a simple note"
    curl -X POST -H 'User-Agent: shpub' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Authorization: Bearer abc' -d 'h=entry' -d 'content=a simple note' 'http://known.bogo/micropub/endpoint'
    Post created at server
    http://known.bogo/2016/a-simple-note


===========
About shpub
===========
shpub's homepage is http://cweiske.de/shpub.htm


Source code
===========
shpub's source code is available from http://git.cweiske.de/shpub.git
or the `mirror on github`__.

__ https://github.com/cweiske/shpub


License
=======
shpub is licensed under the `AGPL v3 or later`__.

__ http://www.gnu.org/licenses/agpl.html


Author
======
shpub was written by `Christian Weiske`__.

__ http://cweiske.de/
