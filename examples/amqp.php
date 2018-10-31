<?php

/*
 * This file is part of the php-gelf package.
 *
 * (c) Benjamin Zikarsky <http://benjamin-zikarsky.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

if (!\extension_loaded('amqp')) {
    die('This example requires ext-amqp');
}

$connection = new \AMQPConnection([
    'host' => 'localhost',
    'login' => '',
    'password' => ''
]);
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
$message = new Gelf\Message('Foobar!', \Psr\Log\LogLevel::ALERT);
$message->setFullMessage('There was a foo in bar')
    ->setFacility('example-facility');

$publisher->publish($message);

// The implementation of PSR-3 is encapsulated in the Logger-class.
// It provides high-level logging methods, such as alert(), info(), etc.
$logger = new Gelf\Logger($publisher, 'example-facility');

// Now we can log...
$logger->alert('Foobaz!');
