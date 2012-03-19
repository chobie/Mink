<?php

namespace Behat\Mink\Driver;

use Behat\Mink\Session,
    Behat\Mink\Element\NodeElement,
    Behat\Mink\Exception\DriverException,
    Behat\Mink\Exception\UnsupportedDriverActionException;

use Behat\Mink\Driver\Webkit\Browser;

/*
 * This file is part of the Behat\Mink.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Webkit driver.
 *
 * @author Shuhei Tanuma <chobieee@gmail.com>
 */
class WebkitDriver implements DriverInterface
{
    /* @var boolean */
    private $started = false;

    /* @var Webkit\Browser */
    private $browser;

    /* @var Session */
    private $session;


    /**
     * @param array $options
     */
    public function __construct($options = array())
    {
        $browser = new Browser($options);
        $this->browser = $browser;
    }

    /**
     * Sets driver's current session.
     *
     * @param   Behat\Mink\Session  $session
     */
    public function setSession(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Starts driver.
     */
    public function start()
    {
        $this->started = true;
        $this->browser->start();
    }

    /**
     * Checks whether driver is started.
     *
     * @return  Boolean
     */
    public function isStarted()
    {
        return $this->started;
    }

    /**
     * Stops driver.
     */
    public function stop()
    {
        $this->started = false;
        $this->browser->stop();
    }

    /**
     * Resets driver.
     */
    public function reset()
    {
        $this->browser->reset();
    }

    /**
     * Visit specified URL.
     *
     * @param   string  $url    url of the page
     */
    public function visit($url)
    {
        $this->browser->visit($url);
    }

    /**
     * Returns current URL address.
     *
     * @return  string
     */
    public function getCurrentUrl()
    {
        return $this->browser->currentUrl();
    }

    /**
     * Reloads current page.
     */
    public function reload()
    {
        $this->browser->executeScript('location.reload');
    }

    /**
     * Moves browser forward 1 page.
     */
    public function forward()
    {
        $this->browser->executeScript('history.forward()');
    }

    /**
     * Moves browser backward 1 page.
     */
    public function back()
    {
        $this->browser->executeScript('history.back()');
    }

    /**
     * Sets HTTP Basic authentication parameters
     *
     * @param   string|false    $user       user name or false to disable authentication
     * @param   string          $password   password
     */
    public function setBasicAuth($user, $password)
    {
        $this->browser->setHeader("Authorizatoin", base64_encode($user . ":" . $password));
    }

    /**
     * Sets specific request header on client.
     *
     * @param   string  $name
     * @param   string  $value
     */
    public function setRequestHeader($name, $value)
    {
        $this->browser->setHeader($name, $value);
    }

    /**
     * Returns last response headers.
     *
     * @return  array
     */
    public function getResponseHeaders()
    {
        return $this->browser->responseHeader();
    }

    /**
     * Sets cookie.
     *
     * @param   string  $name
     * @param   string  $value
     */
    public function setCookie($name, $value = null)
    {
        $this->browser->setCookies($name, $value);
    }

    /**
     * Returns cookie by name.
     *
     * @param   string  $name
     *
     * @return  string|null
     */
    public function getCookie($name)
    {
        $cookies = $this->browser->getCookies();
        //@todo parse cookies and obtain 1 cookie with specified name
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Returns last response status code.
     *
     * @return  integer
     */
    public function getStatusCode()
    {
        return $this->browser->statusCode();
    }

    /**
     * Returns last response content.
     *
     * @return  string
     */
    public function getContent()
    {
        return $this->browser->body();
    }

    /**
     * Finds elements with specified XPath query.
     *
     * @param   string  $xpath
     *
     * @return  array           array of Behat\Mink\Element\NodeElement
     */
    public function find($xpath)
    {
        $nodes = $this->browser->find($xpath);

        $elements = array();
        foreach ($nodes as $offset => $node_id) {
            $elements[] = new NodeElement(sprintf('(%s)[%d]', $xpath, $offset+1), $this->session);
        }

        return $elements;
    }

    /**
     * Returns element's tag name by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getTagName($xpath)
    {
        $this->browser->getTagName($xpath);
    }

    /**
     * Returns element's text by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getText($xpath)
    {
        $node = $this->browser->findOne($sourceXpath);
        return $this->browser->invoke("text", $node);
    }

    /**
     * Returns element's html by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  string
     */
    public function getHtml($xpath)
    {
        // TODO: Implement getHtml() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Returns element's attribute by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  mixed
     */
    public function getAttribute($xpath, $attr)
    {
        return $this->browser->invoke("attribute",
            $this->browser->findOne($xpath),
            $attr);
    }

    /**
     * Returns element's value by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  mixed
     */
    public function getValue($xpath)
    {
        return $this->browser->invoke("value", $this->browser->findOne($xpath));
    }

    /**
     * Sets element's value by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $value
     */
    public function setValue($xpath, $value)
    {
        $this->browser->setValue($xpath, $value);
    }

    /**
     * Checks checkbox by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function check($xpath)
    {
        $node = $this->browser->findOne($xpath);
        return $this->browser->invoke("set", $node, "true");
    }

    /**
     * Unchecks checkbox by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function uncheck($xpath)
    {
        $node = $this->browser->findOne($xpath);
        return $this->browser->invoke("set", $node, "false");
    }

    /**
     * Checks whether checkbox checked located by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  Boolean
     */
    public function isChecked($xpath)
    {
        $node = $this->browser->findOne($sourceXpath);
        return $this->browser->invoke("attribute", $node, "checked");
    }

    /**
     * Selects option from select field located by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $value
     * @param   Boolean $multiple
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        $path = $xpath . "/option[(text()='$value' or @value='$value')]";

        $node = $this->browser->findOne($path);
        $this->browser->invoke("selectOption",$node);
    }

    /**
     * Clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function click($xpath)
    {
        $this->browser->click($xpath);
    }

    /**
     * Double-clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function doubleClick($xpath)
    {
        // TODO: Implement doubleClick() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Right-clicks button or link located by it's XPath query.
     *
     * @param   string  $xpath
     */
    public function rightClick($xpath)
    {
        // TODO: Implement rightClick() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Attaches file path to file field located by it's XPath query.
     *
     * @param   string  $xpath
     * @param   string  $path
     */
    public function attachFile($xpath, $path)
    {
        $this->browser->setValue($xpath, $path);
    }

    /**
     * Checks whether element visible located by it's XPath query.
     *
     * @param   string  $xpath
     *
     * @return  Boolean
     */
    public function isVisible($xpath)
    {
        $node = $this->browser->findOne($xpath);
        return $this->browser->invoke("visible",$node);
    }

    /**
     * Simulates a mouse over on the element.
     *
     * @param   string  $xpath
     */
    public function mouseOver($xpath)
    {
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Brings focus to element.
     *
     * @param   string  $xpath
     */
    public function focus($xpath)
    {
        // TODO: Implement blur() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Removes focus from element.
     *
     * @param   string  $xpath
     */
    public function blur($xpath)
    {
        // TODO: Implement blur() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Presses specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        //$node = $this->browser->findOne($xpath);
        //$this->browser->invoke("keypress", $node, $char, $modifier);
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Pressed down specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        // TODO: Implement keyDown() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Pressed up specific keyboard key.
     *
     * @param   string  $xpath
     * @param   mixed   $char       could be either char ('b') or char-code (98)
     * @param   string  $modifier   keyboard modifier (could be 'ctrl', 'alt', 'shift' or 'meta')
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        // TODO: Implement keyUp() method.
        throw new UnsupportedDriverActionException(__FUNCTION__ . " does not support yet", $this);
    }

    /**
     * Drag one element onto another.
     *
     * @param   string  $sourceXpath
     * @param   string  $destinationXpath
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        $source = $this->browser->findOne($sourceXpath);
        $dest   = $this->browser->findOne($destinationXpath);

        $this->browser->invoke("dragTo", $source, $dest);
    }

    /**
     * Executes JS script.
     *
     * @param   string  $script
     */
    public function executeScript($script)
    {
        return $this->browser->executeScript($script);
    }

    /**
     * Evaluates JS script.
     *
     * @param   string  $script
     *
     * @return  mixed           script return value
     */
    public function evaluateScript($script)
    {
        return $this->browser->evaluateScript($script);
    }

    /**
     * Waits some time or until JS condition turns true.
     *
     * @param   integer $time       time in milliseconds
     * @param   string  $condition  JS condition
     */
    public function wait($time, $condition)
    {
        $script = "return $condition;";
        $start  = 1000 * microtime(true);
        $end    = $start + $time;

        while (1000 * microtime(true) < $end &&
               !$this->browser->evaluateScript($script)) {
            sleep(0.1);
        }

    }

    /**
     * save image
     *
     * @param $path
     * @param int $width
     * @param int $height
     */
    public function render($path, $width = 1024 , $height = 100)
    {
        $this->browser->render($path, $width, $height);
    }

}