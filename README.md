```php
$client = new ThriftGuzzle\PromiseClient(
    '\\service\\example\\EchoServiceIf', 
    new ThriftGuzzle\TGuzzleTransport('http://127.0.0.1:8080/service-example/echo-service'), 
    new Thrift\Factory\TBinaryProtocolFactory(true, true)
);


$promise = $client->echo('123');
var_dump($promise->wait());

$promise1 = $client->echo('456');
$promise2 = $client->echo('789');

var_dump($promise1->wait());
var_dump($promise2->wait());

```

