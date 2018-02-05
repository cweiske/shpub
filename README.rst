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


Installation
============
After downloading ``shpub-x.y.z.phar``, you can either use it directly::

    $ php /path/to/shpub-x.y.z.phar --version

or make it more easily accessible::

    $ mv /path/to/shpub-x.y.z.phar /usr/local/bin/shpub
    $ chmod +x /usr/local/bin/shpub
    $ shpub --version

You might need ``sudo`` to be able to copy it into the ``/usr/local/bin/``
directory.

If you're running from the git checkout, start it as follows::

    $ ./bin/shpub.php --version


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

    $ shpub connect http://mywebsite

In case there are multiple users on the same server::

    $ shpub connect http://sharedwebsite http://shareswebsite/user

If you pass a third parameter, then that will be the name of the connection.
You can select a specific server/connection with ``-s`` on all commands.


List configured servers/connections
===================================
::

    $ shpub server
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

By default ``shpub`` sends data form-encoded.
To send JSON requests, use the ``--json`` option.


Create a like
=============
::

    $ shpub like http://example.org/
    Like created at server
    http://anoweco.bogo/comment/23.htm

Create a reply
==============
::

    $ shpub reply http://example.org/ "Hey, cool!"
    Reply created at server
    http://anoweco.bogo/comment/42.htm


Create a note
=============
A normal note::

    $ shpub note "oh this is cool!"
    Note created at server
    http://known.bogo/2016/oh-this-is-cool.htm

Note with an image::

    $ shpub note -f image.jpg "this is so cute"
    Note created at server
    http://known.bogo/2016/this-is-so-cute

You can use ``-f`` several times to upload multiple files.

URL image upload::

    $ shpub note -f http://example.org/1.jpg "img url!"
    Note created at server
    http://known.bogo/2016/img-url

Load note content from a file::

    $ shpub note - < /path/to/file.txt
    Note created at server
    http://known.bogo/2017/some-note


Custom post types
=================
You may create custom post types with the ``x`` command.
This is useful if your micropub endpoint supports additional types,
like `known <http://withknown.com/>`__'s
`"annotation" type <https://cweiske.de/tagebuch/micropub-comments-known.htm>`__
(comments and likes for posts).

Create a comment to a known post::

    $ shpub x annotation\
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

    $ shpub delete http://known.bogo/2016/like

Restore a deleted post::

    $ shpub undelete http://known.bogo/2016/like


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

    $ shpub targets
    IndieNews
     https://news.indieweb.org/en

Then specify it when creating a post::

    $ shpub article -x mp-syndicate-to=https://news.indieweb.org/en title text

============
File uploads
============
Most post types allow file uploads. Simply use ``-f``::

    $ shpub note -f path/to/image.jpg "image test"
    Note created at server
    http://known.bogo/2016/image-test

The media endpoint is used automatically if the micropub endpoint has one.
To force ``shpub`` to directly upload the file and skip the media endpoint,
use the ``--direct-upload`` option::

    $ shpub note --direct-upload -f path/to/image.jpg "direct upload"

Use the ``upload`` command to upload files to the media endpoint without
creating a post::

    $ shpub upload /path/to/file.jpg /path/to/file2.jpg
    Uploaded file /path/to/file.jpg
    http://test.bogo/micropub-media-endpoint/1474362040.2941/file.jpg
    Uploaded file /path/to/file2.jpg
    http://test.bogo/micropub-media-endpoint/1474362040.3383/file2.jpg


=========
Debugging
=========
To debug ``shpub`` or your micropub endpoint, use the ``--debug`` option
to see ``curl`` command equivalents to the shpub HTTP requests::

    $ shpub -s known -d note "a simple note"
    curl -X POST -H 'User-Agent: shpub' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Authorization: Bearer abc' -d 'h=entry' -d 'content=a simple note' 'http://known.bogo/micropub/endpoint'
    Post created at server
    http://known.bogo/2016/a-simple-note


See curl commands only
======================
You may use the ``--dry-run`` option to make shpub not send any modifying
HTTP requests (e.g. POST and PUT).

Together with ``--debug`` you can use this to get curl commands without sending
anything to the server::

    $ shpub --debug --dry-run like example.org
    curl -X POST -H 'User-Agent: shpub' -H 'Content-Type: application/x-www-form-urlencoded' -H 'Authorization: Bearer cafe' -d 'h=entry' -d 'like-of=http://example.org' 'http://anoweco.bogo/micropub.php'
    Like created at server
    http://example.org/fake-response


===========
Development
===========

Releasing a new version
=======================

#. Add notes to ``ChangeLog``
#. Update version number in ``build.xml`` and ``src/shpub/Cli.php``
#. Run ``phing``
#. Commit and tag the version
#. In the ``cweiske.de`` directory, run ``./scripts/update-shpub.sh``


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
