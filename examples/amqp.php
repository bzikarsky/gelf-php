<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Note, You need install "amqp" extension for PHP.
// @link http://php.net/manual/pl/book.amqp.php

$connection = new \AMQPConnection(array(
    'host' => 'localhost',
    'login' => '',
    'password' => ''
));
$connection->connect();

$channel = new \AMQPChannel($connection);

$exchange = new \AMQPExchange($channel);
$exchange->setName('log-messages');
$exchange->setType(AMQP_EX_TYPE_FANOUT);
$exchange->declareExchange();

$queue = new \AMQPQueue($channel);
$queue->setName('log-messages');
$queue->setFlags(AMQP_DURABLE);
$queue->declareQueue();
$queue->bind($exchange->getName());

$transport = new Gelf\Transport\AmqpTransport($exchange, $queue);

$publisher = new Gelf\Publisher();
$publisher->addTransport($transport);

// Now we can create custom messages and publish them
$message = new Gelf\Message();
$message->setShortMessage("Foobar!")
    ->setLevel(\Psr\Log\LogLevel::ALERT)
    ->setFullMessage("There was a foo in bar")
    ->setFacility("example-facility");
$publisher->publish($message);

// The implementation of PSR-3 is encapsulated in the Logger-class.
// It provides high-level logging methods, such as alert(), info(), etc.
$logger = new Gelf\Logger($publisher, "example-facility");

// Now we can log...
$logger->alert("Foobaz!");
