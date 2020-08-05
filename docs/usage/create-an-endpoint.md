# Ulrack Web Application - Create an endpoint

After the application is setup, an endpoint can be created. This requires 6
files:
- `composer.json`
- `locator.php`
- `configuration/route/my.route.json`
- `configuration/route-group/my.route.group.json`
- `configuration/services/my.services.json`
- `src/Endpoint/MyEndpoint.php`

The last four files can have any other (more appropriate) name. The endpoint
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

## configuration/route/my.route.json

This file will take care of the registration of the route. The contents will
look something along the lines of:
```json
{
    "$schema": "route.schema.json",
    "key": "my-route",
    "path": "/",
    "service": "services.my-endpoint",
    "methods": [
        "GET"
    ],
    "outputService": "services.web.handler.output"
}
```

This will create a route which will function as the "home" endpoint.

## configuration/route-group/my.route.group.json

This file will take care of the registration of the route group. The contents
will look something along the lines of:
```json
{
    "$schema": "route-group.schema.json",
    "key": "main",
    "ports": [
        80,
        443
    ],
    "hosts": [
        "*.*.*",
        "*.*"
    ],
    "route": "my-route",
    "errorRegistryService": "services.web.errors.default.api.registry"
}
```

This will setup a route group, which accepts all primary domains and
sub-domains. It uses the previously configured home route, as the default home
endpoint.

## configuration/services/my.services.json

This file is required to register the service, which will be retrieved in the
router. The contents of this file will look like the following:
```json
{
    "my-endpoint": {
        "class": "\\MyVendor\\MyEndpointPackage\\Endpoint\\MyEndpoint"
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
