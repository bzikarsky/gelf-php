<?php

require('gelf.php');
require('longmessage');

$gelf = new GELFMessage('localhost', 12201);

$gelf->setShortMessage('something is broken.');
//$gelf->setFullMessage($longData);
$gelf->setFullMessage("lol full message!");
$gelf->setHost('somehost');
$gelf->setLevel(2);
$gelf->setFile('/var/www/example.php');
$gelf->setLine(1337);
$gelf->send();
