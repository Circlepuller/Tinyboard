Tinyboard - A lightweight PHP imageboard.
========================================================

About
------------
Tinyboard is a free light-weight, fast, highly configurable and user-friendly imageboard software package. It is written in PHP and has few dependencies.

History
------------
Tinyboard is defunct no longer. Circlepuller and others aim to modernize and optimize the additions that the vichan fork brought into this updated software.

Requirements
------------
1.	PHP >= 7.2
2.	MySQL/MariaDB server >= 5.5.3
3.	[mbstring](http://www.php.net/manual/en/mbstring.installation.php) 
4.	[PHP GD](http://www.php.net/manual/en/intro.image.php)
5.	[PHP PDO](http://www.php.net/manual/en/intro.pdo.php)

We try to make sure Tinyboard is compatible with all major web servers and
operating systems. Tinyboard does not include an Apache ```.htaccess``` file nor does
it need one.

### Recommended
1.	ImageMagick (command-line ImageMagick or GraphicsMagick preferred).
2.	[APC (Alternative PHP Cache)](http://php.net/manual/en/book.apc.php),
	[XCache](http://xcache.lighttpd.net/) or
	[Redis](http://pecl.php.net/package/redis)

Contributing
------------
You can contribute to Tinyboard by:
*	Developing patches/improvements/translations and using GitHub to submit pull requests
*	Providing feedback and suggestions
*	Writing/editing documentation

If you need help developing a patch, please join our IRC channel.

Installation
-------------
1.	Download and extract Tinyboard to your web directory or get the latest
	development version with:

        git clone git://github.com/Circlepuller/Tinyboard.git

2.  Download and install required external third-party dependencies
    into the ```vendor/``` directory with:

		composer install
			
3.	Navigate to ```install.php``` in your web browser and follow the
	prompts.
4.	Tinyboard should now be installed. Log in to ```mod.php``` with the
	default username and password combination: **admin / password**.

Please remember to change the administrator account password.

See also: [Configuration Basics](https://web.archive.org/web/20121003095922/http://tinyboard.org/docs/?p=Config).

Upgrade
-------
To upgrade from any version of Tinyboard (or vichan):

Either run ```git pull``` to update your files, if you used git, or
backup your ```inc/instance-config.php```, replace all your files in place
(don't remove boards etc.), then put ```inc/instance-config.php``` back and
finally run ```install.php```.

Support
--------
Tinyboard is still beta software -- there are bound to be bugs. If you find a
bug, please report it.

If you need assistance with installing, configuring, or using vichan, you may
find support from a variety of sources:

*	If you're unsure about how to enable or configure certain features, make
	sure you have read the comments in ```inc/config.php```.
*	Ask for help on [AwsumChan's Meta/Support board](https://awsumchan.org/aw/).
*	You can join this IRC channel for support
	[m.ircd.moe #dev](irc://m.ircd.moe/dev)
*	Legacy Tinyboard documentation can be found [here](https://web.archive.org/web/20121016074303/http://tinyboard.org/docs/?p=Main_Page).

CLI tools
-----------------
There are a few command line interface tools, based on Tinyboard-Tools. These need
to be launched from a Unix shell account (SSH, or something). They are located in a ```tools/```
directory.

You actually don't need these tools for your imageboard functioning, they are aimed
at the power users. You won't be able to run these from shared hosting accounts
(i.e. all free web servers).

Localization
------------
Wanting to have Tinyboard in your language? You can contribute your translations at this URL:

https://www.transifex.com/projects/p/tinyboard-vichan-devel/

Oekaki
------
Tinyboard makes use of [wPaint](https://github.com/websanova/wPaint) for oekaki. After you pull the repository, however, you will need to download wPaint separately using git's `submodule` feature. Use the following commands:

```
git submodule init
git submodule update
```

To enable oekaki, add all the scripts listed in `js/wpaint.js` to your `instance-config.php`.

WebM support
------------
Read `inc/lib/webm/README.md` for information about enabling webm.

Tinyboard API
----------
Tinyboard provides by default a 4chan-compatible JSON API. For documentation on this, see:
https://github.com/vichan-devel/vichan-API/ .

License
--------
See [LICENSE.md](http://github.com/Circlepuller/Tinyboard/blob/master/LICENSE.md).
