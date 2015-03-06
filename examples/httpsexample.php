<?php

require_once __DIR__ . '/../vendor/autoload.php';

// Set the scheme to ssl, so it's working with https as well

$transport = new Gelf\Transport\HttpTransport("your-graylog-url.com", 12201, "/gelf", "ssl");
$transport->setAuthentication("user", "password");

$publisher = new Gelf\Publisher();
$publisher->addTransport(($transport));

// Now we can create custom messages and publish them
$message = new Gelf\Message();
$message->setShortMessage("Foobar!")
    ->setLevel(\Psr\Log\LogLevel::ALERT)
    ->setFullMessage("There was a foo in bar")
    ->setFacility("example-facility")
;

$publisher->publish($message);
// The implementation of PSR-3 is encapsulated in the Logger-class.
// It provides high-level logging methods, such as alert(), info(), etc.
$logger = new Gelf\Logger($publisher, "example-facility");
// Now we can log...
$logger->alert("Foobaz!");