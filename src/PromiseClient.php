<?php

namespace ThriftGuzzle;

use Thrift\Exception\TApplicationException;
use Thrift\Factory\TProtocolFactory;
use Thrift\Transport\TMemoryBuffer;
use Thrift\Type\TMessageType;

class PromiseClient
{

    private $interfaceName;

    /**
     *
     * @var TProtocolFactory
     */
    private $inputProtocolFactory;

    /**
     *
     * @var TProtocolFactory
     */
    private $outputProtocolFactory;

    /**
     *
     * @var TGuzzleTransport
     */
    private $transport;

    public function __construct($interfaceName, TGuzzleTransport $transport, TProtocolFactory $inputProtocolFactory, TProtocolFactory $outputProtocolFactory = null)
    {
        if (static::endsWith($interfaceName, 'If')) {
            $this->interfaceName = substr($interfaceName, 0, -2);
        } else {
            $this->interfaceName = $interfaceName;
        }

        $this->transport = $transport;

        $this->inputProtocolFactory = $inputProtocolFactory;
        if ($outputProtocolFactory) {
            $this->outputProtocolFactory = $outputProtocolFactory;
        } else {
            $this->outputProtocolFactory = $inputProtocolFactory;
        }
    }

    public function __call($funcName, $arguments)
    {

        $reflection = new \ReflectionMethod($this->interfaceName . 'If', $funcName);

        $params = array_map(function ($p) {
            return $p->name;
        }, $reflection->getParameters());

        $argsClassName = $this->interfaceName . '_' . $funcName . '_args';

        $args = new $argsClassName(array_combine($params, $arguments));

        $protocol = $this->outputProtocolFactory->getProtocol($this->transport);

        $protocol->writeMessageBegin($funcName, TMessageType::CALL, 0);
        $args->write($protocol);
        $protocol->writeMessageEnd();
        $promise = $protocol->getTransport()->async();

        $promise = $promise->then(function ($response) use ($funcName) {
            $body = $response->getBody();

            $rseqid = 0;
            $fname = null;
            $mtype = 0;

            $input = $this->inputProtocolFactory->getProtocol(new TMemoryBuffer($body));

            $input->readMessageBegin($fname, $mtype, $rseqid);
            if ($mtype == TMessageType::EXCEPTION) {
                $x = new TApplicationException();
                $x->read($input);
                $input->readMessageEnd();
                throw $x;
            }
            $resultClass = $this->interfaceName . '_' . $funcName . '_result';

            $result = new $resultClass;
            $result->read($input);
            $input->readMessageEnd();
            if ($result->success !== null) {
                return $result->success;
            } else {
                return false;
            }
        });

        return $promise;

    }

    private static function endsWith($str, $sub)
    {
        return substr($str, strlen($str) - strlen($sub)) === $sub;
    }

}
