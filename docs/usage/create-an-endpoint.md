# Ulrack Web Application - Create an endpoint

After the application is setup, an endpoint can be created. This requires 6
files:
- `composer.json`
- `locator.php`
- `configuration/services/my.routes.json`
- `configuration/services/my.services.json`
- `src/Endpoint/MyEndpoint.php`
- `src/Router/MyRouter.php`

The last five files can have any other (more appropriate) name. The endpoint
can also be configured inside a project.

## composer.json

The `composer.json` file is required to automatically load the `locator.php`
file. This can be easily done by adding the following node, to the file:
```json
{
    "autoload": {
        "psr-4": {
            "MyVendor\\MyEndpointPackage\\": "src/"
        },
        "files": [
            "locator.php"
        ]
    }
}
```

## locator.php

The `locator.php` file is used to determine the root of the package, so
configuration can be autoloaded within the core of Ulrack. The contents of this
file should be the following:
```php
<?php

use GrizzIt\Configuration\Component\Configuration\PackageLocator;

PackageLocator::registerLocation(__DIR__);

```

## configuration/services/my.routes.json

This file will take care of the registration of the router and add the router to
the default router, so it is accepted immediatly. The contents will look
something along the lines of:
```json
{
    "services": {
        "default.router": {
            "class": "\\MyVendor\\MyEndpointPackage\\Router\\DefaultRouter"
        },
        "my-endpoint": {
            "class": "\\MyVendor\\MyEndpointPackage\\Endpoint\\MyEndpoint"
        }
    },
    "tags": {
        "my.tag": {
            "trigger": "triggers.web.main.routers",
            "service": "services.default.router"
        }
    }
}
```

## src/Endpoint/MyEndpoint.php

The endpoint uses the `EndpointInterface` from the `ulrack/web` package. The
implementation would look like this:
```php
<?php

namespace MyVendor\MyEndpointPackage\Endpoint;

use Ulrack\Web\Common\Endpoint\InputInterface;
use Ulrack\Web\Common\Endpoint\OutputInterface;
use Ulrack\Web\Common\Endpoint\EndpointInterface;

class MyEndpoint implements EndpointInterface
{
    /**
     * Invokes the endpoint.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $output->setContentType('application/json');
        $output->setOutput($output->getAcceptedContentTypes());
    }
}

```

## src/Router/MyRouter.php

The router uses the `RouterInterface` from the `ulrack/web` package. The
implementation would look like this:
```php
<?php

namespace MyVendor\MyEndpointPackage\Router;

use Ulrack\Web\Common\Endpoint\InputInterface;
use Ulrack\Web\Common\Endpoint\OutputInterface;
use Ulrack\Web\Common\Router\RouterInterface;

class MyRouter implements RouterInterface
{
    /**
     * Determines whether the router accepts the request.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return bool
     */
    public function accepts(
        InputInterface $input,
        OutputInterface $output
    ): bool {
        return true;
    }

    /**
     * Resolves the request to an endpoint, executes it and renders the response.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    public function __invoke(
        InputInterface $input,
        OutputInterface $output
    ): void {
        $input->setParameter('endpoint', 'services.my-endpoint');
    }
}

```

To load the new configuration clear the caches by running:
```bash
bin/application cache clear
```

Then to validate the configuration run the command:
```bash
bin/application validate configuration
```

When everything is configured correctly, the endpoint should render a list of
content types sent in the request.

## Further reading

[Back to usage index](index.md)

[Setup](setup.md)
