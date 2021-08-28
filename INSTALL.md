
Installing Florrie - For Fun and Profit
========

Copyright [Jacob Hume (Windigo)](http://fragdev.com/), licensed
under the AGPL v3.

Make sure to check REQUIREMENTS.md before installing!

Installing
--------

To install Florrie, you can clone it straight from the source! The following
command will work if you have shell access to your host:

git clone https:// (TODO - get URL!)
git submodule init
git submodule update

You'll need the following PHP extensions enabled: (for development)

* curl
* ext-dom
* mbstring

After you have a copy of the source code, make sure your web server can write to
the following directories:

* config (TODO: Not created upon git pull!)
* strips

From there, you should be able to continue with the install process by simply
visiting your web site in your browser. Florrie will walk you through the
install process from there.
