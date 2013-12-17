gelf-php[ ![Build Status](https://travis-ci.org/bzikarsky/gelf-php.png?branch=master)](https://travis-ci.org/bzikarsky/gelf-php) [![Dependency Status](https://www.versioneye.com/user/projects/52591e23632bac78d0000047/badge.png)](https://www.versioneye.com/user/projects/52591e23632bac78d0000047) [![Coverage Status](https://coveralls.io/repos/bzikarsky/gelf-php/badge.png?branch=master)](https://coveralls.io/r/bzikarsky/gelf-php?branch=master) [![SensioLabsInsight](https://insight.sensiolabs.com/projects/5aaa17f2-cc43-49bf-a23e-cc1585eaa3c2/mini.png)](https://insight.sensiolabs.com/projects/5aaa17f2-cc43-49bf-a23e-cc1585eaa3c2)
========

A php implementation to send log-files to a gelf compatible backend like [Graylog2](http://graylog2.org/).
This library conforms to the PSR standards in regards to structure ([0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)),
coding-style ([1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md),
[2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md))
and logging ([3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)).

It's a loosely based on the original [Graylog2 gelf-php](https://github.com/Graylog2/gelf-php)
and [mlehner's fork](https://github.com/mlehner/gelf-php).

Usage
-----

### Recommended installation via composer:

Add gelf-php to `composer.json`:

    "require": {
       // ...
       "bzikarsky/gelf-php": "1.0.*"
       // ...
    }

Reinstall dependencies: `composer install`

### Examples

For usage examples, go to [/examples](https://github.com/bzikarsky/gelf-php/tree/master/examples).

License
-------

The library is licensed under the MIT license. For details check out the LICENSE file.


Development & Contributing
--------------------------

You are welcome to modify, extend and bugfix all you like. :-)
In case you want to use the nifty [PhpStorm-IDE](http://www.jetbrains.com/phpstorm/), there is an OSS-license for this
project, you can use. Just contact me [@bzikarsky](https://twitter.com/bzikarsky).

### Tools
1. [composer](http://getcomposer.org), preferably a system-wide installation as `composer`
2. [PHPUnit](http://phpunit.de/manual/current/en/installation.html)
3. Optional: [PHP_CodeSniffer](https://github.com/squizlabs/PHP_CodeSniffer) for PSR-X-compatibility checks

### Steps
1. Clone repository and cd into it: `git clone git@github.com:bzikarsky/gelf-php && cd gelf-php`
2. Install dependencies: `composer install --dev`
3. Run unit-tests: `phpunit`
4. Check PSR compatibility: `phpcs --standard=PSR2 src`
