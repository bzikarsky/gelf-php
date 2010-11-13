<?php

require('gelf.php');

$gelf = new GELFMessage('localhost', 12201);

$gelf->setShortMessage('something is broken.');
$gelf->setFullMessage("lol full message!");
$gelf->setHost('somehost');
$gelf->setLevel(2);
$gelf->setFile('/var/www/example.php');
$gelf->setLine(1337);
$gelf->setAdditional("something", "foo");
$gelf->setAdditional("something_else", "bar");
$gelf->send();
