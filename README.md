URL builder library for PHP
=================

[![Latest Stable Version](https://poser.pugx.org/romeOz/rock-url/v/stable.svg)](https://packagist.org/packages/romeOz/rock-url)
[![Total Downloads](https://poser.pugx.org/romeOz/rock-url/downloads.svg)](https://packagist.org/packages/romeOz/rock-url)
[![Build Status](https://travis-ci.org/romeOz/rock-url.svg?branch=master)](https://travis-ci.org/romeOz/rock-url)
[![HHVM Status](http://hhvm.h4cc.de/badge/romeoz/rock-url.svg)](http://hhvm.h4cc.de/package/romeoz/rock-url)
[![Coverage Status](https://coveralls.io/repos/romeOz/rock-url/badge.svg?branch=master)](https://coveralls.io/r/romeOz/rock-url?branch=master)
[![License](https://poser.pugx.org/romeOz/rock-url/license.svg)](https://packagist.org/packages/romeOz/rock-url)

[Rock URL on Packagist](https://packagist.org/packages/romeOz/rock-url)

Features
-------------------

 * Module for [Rock Framework](https://github.com/romeOz/rock)

Installation
-------------------

From the Command Line:

```composer require romeoz/rock-url:*```

In your composer.json:

```json
{
    "require": {
        "romeoz/rock-url": "*"
    }
}
```

Quick Start
-------------------

```php
use rock\url\Url;

// example URL: http://site.com/foo/?page=1

// returns relative URL
(new Url)->getRelative(); // output: /foo/?page=1

// modify URL
Url::set('https://site.com/?page=2#name')->removeAnchor()->getRelative(); 
//output: /?page=2

Url::set('https://site.com/?page=2#name')->removeArgs(['page'])->getAbsolute(); 
//output: https://site.com/#name
```

###Short method `modify()`

```php
Url::modify(['https://site.com/', 'foo' => 'test', '#' => 'name']);
//output: https://site.com/?foo=test#name

Url::modify(['https://site.com/?foo=test#name', '!foo', '!#']);
//output: https://site.com/

// modify current url
Url::modify([foo' => 'test]);
//output: https://site.com/?foo=test
```

Requirements
-------------------
 * **PHP 5.4+**

License
-------------------

Rock URL library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).