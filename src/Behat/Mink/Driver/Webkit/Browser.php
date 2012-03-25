<?php

namespace Behat\Mink\Driver\Webkit;

/*
* This file is part of the Behat\Mink.
* (c) Konstantin Kudryashov <ever.zet@gmail.com>
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

/**
 * Webkit browser.
 *
 * @author Shuhei Tanuma <chobieee@gmail.com>
 */
class Browser
{
    /* @var resource server socket */
    protected $server;

    /* @var int */
    protected $port;

    /* @var resource proc_opened process*/
    protected $process;

    /* @var array options */
    protected $options = array();

    private static $default_options = array(
        'invoke_server' => true,
        'path.webkit_server' => '/Library/Ruby/Gems/1.8/gems/capybara-webkit-0.11.0/bin/webkit_server',
    );

    /**
     * @param array $options
     */
    public function __construct($options = array())
    {
        $this->options = array_merge(self::$default_options, $options);

    }

    /**
     * start server and connect
     */
    public function start()
    {
        if (!is_resource($this->process)) {
            $this->startServer();
            $this->connect();
        }
    }

    /**
     * stop server and disconnect
     */
    public function stop()
    {
        $this->killServer();
        $this->disconnect();
    }

    /**
     * disconnect
     */
    public function disconnect()
    {
        if (is_resource($this->server)) {
            fclose($this->server);
        }
    }

    /**
     * return current server port
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * return current url
     *
     * @return string
     */
    public function currentUrl()
    {
        return $this->command("CurrentUrl");
    }

    /**
     * get tag name
     *
     * @param $xpath
     */
    public function getTagName($xpath)
    {
        $this->invoke("tagName", $this->findOne($xpath));
    }


    /**
     * set form value
     *
     * @param $xpath
     * @param $value
     */
    public function setValue($xpath, $value)
    {
        $node = $this->findOne($xpath);
        $this->invoke("set", $node, $value);
    }

    /**
     * save image
     *
     * @param $path
     * @param int $width
     * @param int $height
     */
    public function render($path, $width = 1024, $height = 680)
    {
        $this->command("Render",array($path,$width,$height));
    }

    /**
     * visit specified url
     *
     * @param $url
     * @return int result status
     */
    public function visit($url)
    {
        return $this->command("Visit", array($url));
    }

    /**
     * dunno
     *
     * @return array
     */
    public function consoleMessage()
    {
        $result = array();
        foreach (explode("\n",$this->command("ConsoleMessages")) as $message) {
            $part = explode("|", $message, 3);
            $result[] = array(
                "source"      => $part[0],
                "line_number" => (int)$part[1],
                "message"     => $part[2]);
        }
        return $result;
    }

    /**
     * returns current response header.
     *
     * @return array
     */
    public function responseHeader()
    {
        $result = array();
        foreach(explode("\n", $this->command("Headers")) as $line){
            list($key, $value) = explode(": ", $line);
            $result[$key] = $value;
        }

        return $result;
    }

    /**
     * set http header
     *
     * @param $key
     * @param $value
     */
    public function setHeader($key, $value)
    {
        $this->command("Header",array($key, $value));
    }

    /**
     * starts webkit_server.
     *
     * webkit_server will listen random port number
     *
     * @throws \RuntimeException
     */
    public function startServer()
    {
        $pipes = array();
        $server_path = $this->options['path.webkit_server'];

        if (!file_exists($server_path)) {
            throw new \RuntimeException("webkit_server does not find");
        }

        $descriptorspec = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w"),
        );

        $process = proc_open($server_path, $descriptorspec, $pipes);
        if (is_resource($process)) {
            $data = fgets($pipes[1]);
            $this->port = $this->discoverServerPort($data);
            $this->process = $process;
        } else {
            throw new \RuntimeException("coudn't lunch webkit_server");
        }

        /* always terminate webkit_server */
        register_shutdown_function(array($this,"registerShutdownHook"));
    }

    /**
     * find one content with specified xpath
     * @param $xpath
     * @return mixed
     *
     * @throws \Exception
     */
    public function findOne($xpath)
    {
        $nodes = $this->find($xpath);
        if (count($nodes)) {
            return array_shift($nodes);
        } else {
            throw new \Exception(
                "element not found"
            );
        }

    }

    /**
     * invoke js function on capybara webkit server.
     *
     * @param string $function_name
     * @param array $parameters
     * @return mixed
     */
    public function invoke()
    {
        return $this->command("Node", func_get_args());
    }

    /**
     * returns http status
     *
     * @return int
     */
    public function statusCode()
    {
        return (int)$this->command("Status");
    }

    /**
     * reload and return current body
     *
     * @return string
     */
    public function source()
    {
        return $this->command("Source");
    }

    /**
     * trigger event
     *
     * @param $xpath
     * @param $event
     * @return bool
     */
    public function trigger($xpath, $event)
    {
        $nodes = $this->find($xpath);
        $node = array_shift($nodes);
        if (!empty($node)) {
            $this->invoke("trigger",$node, $event);
            return true;
        } else {
            return false;
        }
    }

    /**
     * Reset browser session
     *
     * @return void
     */
    public function reset()
    {
        $this->command("Reset");
    }

    /**
     * find on capybara webkit.
     *
     * @param $query
     * @return array internal node ids.
     */
    public function find($query)
    {
        $ret = $this->command("Find",array($query));
        if (empty($ret)) {
            return array();
        }

        return explode(",", $ret);
    }

    /**
     * obtain current frame buffer as string.
     *
     * @return string
     */
    public function body()
    {
        return $this->command("Body");
    }

    /**
     * evaluate specified js and returns it result as json object
     *
     * @param array $script
     */
    public function evaluateScript($script)
    {
        $json = $this->command("Evaluate", array($script));
        return json_decode("[{$json}]", true);
    }

    /**
     * execute javascript
     *
     * @param $script
     */
    public function executeScript($script)
    {
        return $this->command("Execute", array($script));
    }

    /**
     * connect to spawned webkit_serer.
     *
     * @throws \RuntimeException
     */
    protected function connect()
    {
        $server = stream_socket_client("tcp://localhost:{$this->port}",$errno, $errstr, 5);
        if (is_resource($server)) {
            $this->server = $server;
        } else {
            throw new \RuntimeException("could not connect to webkit_server");
        }
    }

    /**
     * send command to webkit_server
     *
     * @param $command
     * @param array $args
     * @return mixed the result
     */
    public function command($command, $args = array())
    {
        fwrite($this->server, $command . "\n");
        fwrite($this->server, count($args) . "\n");

        foreach($args as $arg) {
            fwrite($this->server, strlen($arg) . "\n");
            fwrite($this->server, $arg);
        }
        $this->check();
        return $this->readResponse();
    }

    /**
     * clear cookies
     *
     */
    public function clearCookies()
    {
        $this->command("ClearCookies");
    }

    /**
     * dunno
     *
     * @param null $frame_id_or_index
     * @return mixed
     */
    public function frameFocus($frame_id_or_index = null)
    {
        if (is_string($frame_id_or_index)) {
            return $this->command("FrameFocus", array("", $frame_id_or_index));
        } else if ($frame_id_or_index) {
            return $this->command("FrameFocus", array($frame_id_or_index));
        } else {
            return $this->command("FrameFocus");
        }
    }

    /**
     * click
     *
     * @param $xpath
     */
    public function click($xpath)
    {
        $this->invoke("click", $this->findOne($xpath));
    }

    /**
     * invoke mouse up event
     *
     * @param $xpath
     */
    public function mouseup($xpath)
    {
        $this->invoke("mouseup", $this->findOne($xpath));
    }


    /**
     * invoke mouse down event
     *
     * @param $xpath
     */
    public function mousedown($xpath)
    {
        $this->invoke("mousedown", $this->findOne($xpath));
    }

    /**
     * returns element visibility
     *
     * @param $xpath
     * @return bool
     */
    public function visible($xpath)
    {
        return (bool)$this->invoke("visible", $this->findOne($xpath));
    }

    /**
     * set proxy setting
     *
     * @param array $options
     */
    public function setProxy($options = array())
    {
        $options = array_merge(array(
            "host" => "localhost",
            "port" => 0,
            "user" => "",
            "pass" => "",
            ),
            $options
        );
        $this->command("SetProxy", array(
            $options['host'],
            $options['port'],
            $options['user'],
            $options['pass']
        ));
    }

    /**
     * clear proxy setting
     */
    public function clearProxy()
    {
        $this->command("SetProxy");
    }

    /**
     * set cookies
     *
     * @param string $cookie
     */
    public function setCookies($cookie)
    {
        $this->command("setCookies", array($cookie));
    }

    /**
     * get cookies.
     *
     * @todo parse cookie string
     *
     * @return array
     */
    public function getCookies()
    {
        $result = array();
        foreach(explode("\n",$this->command("GetCookies")) as $line) {
            $line = trim($line);
            if (!empty($line)) {
                $result[] = $line;
            }
        }
        return $result;
    }

    /**
     * remove instance
     *
     */
    public function __destruct()
    {
        $this->killServer();
    }

    /**
     * terminate current webkit_server process
     *
     * @return void
     */
    protected function killServer()
    {
        if (is_resource($this->process)) {
            proc_terminate($this->process);
        }
    }

    /**
     * shutdown hook
     *
     * prevents unterminated webkit_server process.
     */
    public function registerShutdownHook()
    {
        $this->killServer();
    }

    /**
     * check webkit_server response
     *
     * @throws \Exception
     */
    protected function check()
    {
        $error = trim(fgets($this->server));
        if ($error != "ok") {
            throw new \Exception($this->readResponse($this->server));
        }
    }

    /**
     * @return string
     */
    protected function readResponse()
    {
        $data = "";
        $nread = trim(fgets($this->server));

        if ($nread == 0) {
            return $data;
        }

        $read = 0;
        while ($read < $nread) {
            $tmp   = fread($this->server,$nread);
            $read += strlen($tmp);
            $data .= $tmp;
        }
        return $data;
    }

    /**
     * @param $line
     * @return mixed
     * @throws \RuntimeException
     */
    protected function discoverServerPort($line)
    {
        if (preg_match('/listening on port: (\d+)/',$line,$matches)) {
            return (int)$matches[1];
        } else {
            throw \RuntimeException("couldn't find server port");
        }
    }
}