# bh-php

[![Build Status](https://travis-ci.org/zxqfox/bh-php.svg?branch=master)](https://travis-ci.org/zxqfox/bh-php)
 [![Latest Stable Version](https://poser.pugx.org/zxqfox/bh/v/stable.svg)](https://packagist.org/packages/zxqfox/bh)
 [![Total Downloads](https://poser.pugx.org/zxqfox/bh/downloads.svg)](https://packagist.org/packages/zxqfox/bh)

BH is a processor that converts BEMJSON to HTML. Or in other words a template engine.

## Installation & usage


### Installation via composer

```
composer require zxqfox/bh
```

```php
require "vendor/autoload.php";
$bh = new \BEM\BH();
// ...
```

### Manual installation

```
git clone https://github.com/zxqfox/bh-php.git ./vendor/
# or

```

```php
// manual installation
require "vendor/bh-php/index.php"; // if installed manually
$bh = new \BEM\BH();
// ...
```

BH files within a project have `.bh.php` suffix (for example, `page.bh.php`). The file is formed in CommonJS-like format:

```php
return function ($bh) {
	$bh->match(/*...*/);
    // ...
};
```

To load this format use this technique:
```php
$bh = new \BEM\BH();
$fn = include('file.bh.php'); $fn($bh); // done. and nothing in global
// ...
```

Use `apply` method to convert source tree of BEMJSON into an output HTML. Use `processBemJson` method to get an interim result in detailed BEMJSON tree form.

Common use case:

```php
require "vendor/autoload.php";
$bh = new \BEM\BH();
$bh->match('button', function ($ctx) {
    $ctx->tag('button');
});
$bh->processBemJson([ 'block' => 'block' ]); // [ 'block' => 'button', 'mods' => new Mods(), 'tag' => 'button' ]
$bh->apply([ 'block' => 'button' ]); // '<button class="button"></button>'
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

Look at more examples in [https://github.com/bem/bh/blob/master/README.md](README.md) or [https://github.com/bem/bh/blob/master/README.ru.md](README.ru.md).

## License

MIT
