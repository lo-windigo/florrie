Requirements for installing Florrie
===

Copyright [Jacob Hume (Windigo)](http://fragdev.com/), licensed
under the AGPL v3.

Florrie is still in early stages, so it requires a fairly specific setup. Lucky
for you, it's not an unusual one.

The following software is required for Florrie to run:

- PHP 5.3 or greater (lower *may* work, but you're on your own) with the
  following extensions:
	- pdo and mysql
	- gd (recommended: for image resizing)
- MySQL or MariaDB
- A web server capable of handling request rewriting (Apache/mod_rewrite,
  nginx with try_files, etc)

We build Florrie on GNU/Linux, for GNU/Linux. Other *AMP stacks may work, but
again, you're on your own.
