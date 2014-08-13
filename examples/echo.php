<?php

require dirname(__DIR__) . '/vendor/autoload.php';

use Icicle\Coroutine\Coroutine;
use Icicle\Loop\Loop;
use Icicle\StreamSocket\Client;
use Icicle\StreamSocket\Server;

// Connect to server using `nc localhost 60000`.

$coroutine = Coroutine::call(function () {
    $server = Server::create('localhost', 60000);
    
    $handler = Coroutine::async(function (Client $client) {
        try {
            yield $client->ready();
            
            yield $client->write("Hello, want to play echo? Type 'exit' to quit.\n");
			
            while ($client->isReadable()) {
                $data = (yield $client->read());
                
                if ("exit\n" === $data) {
                    yield $client->write("Goodbye!\n");
                    $client->close();
                } else {
                    yield $client->write($data);
                }
            }
        } catch (Exception $e) {
            $client->close();
        }
    });
    
    while ($server->isOpen()) {
        $handler(yield $server->accept());
    }
});

Loop::run();