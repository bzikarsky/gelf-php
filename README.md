gelf-php
========

A php implementation to send log-files to a gelf compatible backend like [Graylog2](http://graylog2.org/).
This library conforms to the PSR standards in regards to structure ([0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md)),
coding-style ([1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md),
[2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md)) 
and logging ([3](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)).

It's a loosely based on the original [Graylog2 gelf-php](https://github.com/Graylog2/gelf-php)
and [mlehner's fork](https://github.com/mlehner/gelf-php).

development
-----------
1. git clone git@git.github.com:bzikarsky/gelf-php
2. cd gelf-php
3. Install [composer](http://getcomposer.org)
4. composer install --dev
5. phpunit
