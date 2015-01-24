# bh-php

[![Gitter](https://badges.gitter.im/Join Chat.svg)](https://gitter.im/bem/talk?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)
 [![Latest Stable Version](https://img.shields.io/packagist/v/bem/bh.svg?style=flat)](https://packagist.org/packages/bem/bh)
 [![Total Downloads](https://img.shields.io/packagist/dm/bem/bh.svg?style=flat)](https://packagist.org/packages/bem/bh)

[![Build Status](https://img.shields.io/travis/bem/bh-php.svg?style=flat)](https://travis-ci.org/bem/bh-php)
 [![Coverage Status](https://img.shields.io/coveralls/bem/bh-php.svg?style=flat)](https://coveralls.io/r/bem/bh-php)
 [![Dependency Status](https://img.shields.io/gemnasium/bem/bh-php.svg?style=flat)](https://gemnasium.com/bem/bh-php)

BH is a processor that converts BEMJSON to HTML. Or in other words a template engine.

Works with `PHP 5.4+` (doesn't work with `HHVM` 'cause it lacks a lot of required functionality)

## Table of Contents

- [Installation](#installation)
- [Friendly Packages](#friendly-packages)
- [Usage](#usage)
- [Conversion](#conversion)

## Friendly Packages

- [Project Stub with BH.PHP tech](https://github.com/bem/project-stub/tree/php-bem-bh)
- [BEM Core Library with BH.PHP templates](https://github.com/zxqfox/bem-core/tree/feature/php-bh-templates%40v2) - tmp branch
- [BEM Components Library with BH.PHP templates](https://github.com/zxqfox/bem-components/tree/feature/php-bh-templates%40v2) - tmp branch

## Installation

### Via composer

Execute in your shell:
```
php composer.phar require bem/bh
```
or (if you have `composer` in your path)
```
composer require bem/bh
```

And use in your code:
```php
require "vendor/autoload.php";
$bh = new \BEM\BH();
// ...
```

### Manual installation

Download using `git` (execute this in your shell):
```
# via git
git clone https://github.com/bem/bh-php.git ./vendor/bem/bh
```

Using `wget` and `tar`:
```
# via wget + tar
wget https://github.com/bem/bh-php/archive/master.tar.gz # download archive
tar -xzvf master.tar.gz --exclude=tests        # extract
[ ! -d ./vendor/bem ] && mkdir ./vendor/bem -p # create vendor director
mv ./bh-php-master ./vendor/bem/bh             # move library to vendor
rm master.tar.gz                               # cleanup
```

Or just download [the latest version](https://github.com/bem/bh-php/archive/master.zip) and unpack to `./vendor/bem/bh` path (or any path you want).

And use in your code:
```php
// manual installation
require "vendor/bem/bh/index.php";
$bh = new \BEM\BH();
// ...
```

## Usage

BH files within a project have `.bh.php` suffix (for example, `page.bh.php`). The file is formed in CommonJS-like format:

```php
return function ($bh) {
    $bh->match(/*...*/);
    // ...
};
```

To load this file format use include and run technique:
```php
// Instantiate BH object
$bh = new \BEM\BH();

// Load and apply matchers to BH object in $bh
$fn = include('file.bh.php');
$fn($bh); // done. and nothing in global

// ...
```

This allows you to have several instances at the moment:
```php
$bh1 = new \BEM\BH();
$bh2 = new \BEM\BH();

// load matchers
$indexMatchers = include('bundles/index/index.bh.php');
$mergedMatchers = include('bundles/merged/merged.bh.php');

// apply them
$indexMatchers($bh1); // bh1 now contains matchers for index page only
$mergedMatchers($bh2); // bh2 now contains all matchers

// use it with the same bemjson data
$bh1->apply($bemjson);
$bh2->apply($bemjson);
```

Use `apply` method to convert source tree of BEMJSON into an output HTML. Use `processBemJson` method to get an interim result in detailed BEMJSON tree form.

Common use case:

```php
require "vendor/autoload.php";
$bh = new \BEM\BH();
$bh->match('button', function ($ctx) {
    $ctx->tag('button');
});

$bh->processBemJson([ 'block' => 'block' ]);
// [ 'block' => 'button', 'mods' => new Mods(), 'tag' => 'button' ]

$bh->apply([ 'block' => 'button' ]);
// '<button class="button"></button>'
```

## Conversion

Working functions for BEMJSON are **templates**. Use `match` method to declare templates. Logic of BEMJSON conversion is declared in a function body.

There are two arguments provided to a template function:
* `$ctx` – instance of `\BEM\Context` class;
* `$json` – instance of `\BEM\Json` class (current BEMJSON tree node).

*NB*: Do not make changes directly in `$json` object. Use methods of `$ctx` object instead. We recommend you to use `$json` object for reading only (see also `$ctx->json()` method).

Syntax:

```php
/**
 * Register matchers
 * @param string|array $expression bem css expression
 * @param closure [$matcher]
 * @return \BEM\BH
 */
$bh->match(/*string*/ $expression, function (\BEM\Context $ctx, \BEM\Json $json) {
    // ... actions
});

// or...
$bh->match([/*string*/ $expression], function (\BEM\Context $ctx, \BEM\Json $json) {
    // ... actions
});

// or...
$bh->match(/*array*/ $matchers = [
    "$expression" => function(\BEM\Context $ctx, \BEM\Json $json) {
        // ... actions
    },
    // ... more matchers
]);
```

Look at more examples in [README.md](https://github.com/bem/bh/blob/master/README.md) or [README.ru.md](https://github.com/bem/bh/blob/master/README.ru.md).

## License

[The MIT Licence](LICENSE).
