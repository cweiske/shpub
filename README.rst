**************************************
shpub - micropub client for your shell
**************************************
Command line micropub client written in PHP.


=====
Usage
=====

Initial setup
=============
::

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

Also try ``-v`` which lists server and user URLs.


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
    Reply created at server
    http://known.bogo/2016/oh-this-is-cool.htm

Note with an image::

    $ ./bin/shpub.php note -f image.jpg "this is so cute"
    Reply created at server
    http://known.bogo/2016/this-is-so-cute.htm

You can use ``-f`` several times to upload multiple files.

URL image upload::

    $ ./bin/shpub.php note -f http://example.org/1.jpg "img url!"
    Reply created at server
    http://known.bogo/2016/img-url.htm
