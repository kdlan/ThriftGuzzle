<?php
namespace thriftguzzle;

use GuzzleHttp\Client;
use Guzzlehttp\Psr7\Request;
use Thrift\Transport\TTransport;

class TGuzzleTransport extends TTransport
{

    private static $client = null;

    private $buffer;

    private $response;

    private $uri;

    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    private static function init()
    {
        if (!static::$client) {
            static::$client = new Client();
        }
    }

    public function isOpen()
    {
        return true;
    }

    /**
     * Open the transport for reading/writing
     *
     * @throws TTransportException if cannot open
     */
    public function open()
    {
    }

    public function read($len)
    {
        if ($len >= strlen($this->response)) {
            return $this->response;
        } else {
            $ret = substr($this->response, 0, $len);
            $this->response = substr($this->response, $len);

            return $ret;
        }
    }

    /**
     * Close the transport.
     */
    public function close()
    {
        $this->buffer = '';
    }

    public function write($buf)
    {
        $this->buffer .= $buf;
    }

    public function flush()
    {
        $response = $this->async()->wait();

        $this->response = $response->getBody();
    }

    public function async()
    {

        static::init();
        $request = new Request('POST', $this->uri, [], $this->buffer);
        $this->buffer = null;
        return static::$client->sendAsync($request);
    }
}
