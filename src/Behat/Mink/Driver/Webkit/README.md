# Webkit Driver - Alternate driver for capybara-webkit's webkit_server -

WebkitDriver drives with capybara-webkit's webkit_server.
webkit_server uses QtWebkit and it's able to control via TCPSocket.
This driver try to port capybara-webkit's API.

# Instlation (for OSX)

````
brew install qt
sudo gem install capybara-webkit
````

also you can use linux environment. you need to install Xvfb and some fonts.

# Usage

visit google.com and fill in search form with "Hello WebkitDriver".
then capture screenshot to /tmp/result.png 

````
<?php
require "vendor/.composer/autoload.php";

use Behat\Mink\Mink,
    Behat\Mink\Session,
    Behat\Mink\Driver\WebkitDriver;

$startUrl = 'http://google.com/';

$mink = new Mink(array(
    'default' => new Session(new WebkitDriver(array())),
    )
);

$mink->setDefaultSessionName('default');
$mink->getSession()->visit($startUrl);
$mink->getSession()->getPage()->fillField("q", "Hello WebkitDriver");
$mink->getSession()->getDriver()->render("/tmp/result.png");
````

# Q and A

* what is the difference between WebkitDriver and Zombie.js ( and PhantomJS)

ZombieJS uses Node.js and simulate browser behavior.
WebkitDriver uses QtWebkit and control via TCPSocket.
PhantomJS uses QtWebkit and control via javascript API (AFAIK).

* my webkit_server doesn't show jpg images. how can I see that?

As Jpeg and some multimedia contents depends Plugins. webkit_server disabled those features.
You can switch the setting with modify WebPage.cpp and re-compile it.
But Actually, I'm not sure about loading plugins. It's pretty complicated things so please fix your hand. 