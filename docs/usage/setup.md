# Ulrack Web Application - Setup

Setting up the Web Application is quite simple.
After the package and its' dependencies are installed create a file from the
root of the application e.g.: `pub/index.php` with the following contents:

```php
<?php

use Ulrack\Kernel\Component\Kernel\Kernel;
use Ulrack\Kernel\Component\Kernel\Manager\CoreManager;
use Ulrack\WebApplication\Component\Application\WebApplication;

require_once __DIR__ . '/../vendor/autoload.php';

$coreManager = new CoreManager(__DIR__ . '/../');

$kernel = new Kernel(
    $coreManager
);

$webApplication = new WebApplication(
    $_SERVER ?? [],
    $_GET ?? [],
    $_POST ?? [],
    $_FILES ?? [],
    $_COOKIES ?? []
);

$kernel->run($webApplication);

```

When a URL is pointing to this file, the application
should be executed.

## Further reading

[Back to usage index](index.md)

[Create an endpoint](create-an-endpoint.md)
