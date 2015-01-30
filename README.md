URL builder library for PHP
=================

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
(new Url)->getRelativeUrl(); // output: /foo/?page=1

// modify URL
Url::set('https://site.com/?page=2#name')->removeAnchor()->getRelativeUrl(); //output: /?page=2
```

Requirements
-------------------
 * **PHP 5.4+**

License
-------------------

Rock URL library is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT).