<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\WebApplication\Component\Application;

use Throwable;
use Ulrack\Kernel\Common\ApplicationInterface;
use Ulrack\Web\Exception\NotAcceptedException;
use Ulrack\Kernel\Common\Manager\ServiceManagerInterface;

class WebApplication implements ApplicationInterface
{
    /**
     * Contains the server variables.
     *
     * @var array
     */
    private array $server;

    /**
     * Contains the get parameters.
     *
     * @var array
     */
    private array $get;

    /**
     * Contains the post values.
     *
     * @var array
     */
    private array $post;

    /**
     * Contains the uploaded file references.
     *
     * @var array
     */
    private array $files;

    /**
     * Contains the cookies.
     *
     * @var array
     */
    private array $cookies;

    /**
     * Constructor.
     *
     * @param array $server
     * @param array $get
     * @param array $post
     * @param array $files
     * @param array $cookies
     */
    public function __construct(
        array $server = [],
        array $get = [],
        array $post = [],
        array $files = [],
        array $cookies = []
    ) {
        $this->server = $server;
        $this->get = $get;
        $this->post = $post;
        $this->files = $files;
        $this->cookies = $cookies;
    }

    /**
     * Runs the application.
     *
     * @param ServiceManagerInterface $serviceManager
     *
     * @return void
     */
    public function run(ServiceManagerInterface $serviceManager): void
    {
        $serviceFactory = $serviceManager->getServiceFactory();
        $webMimeToCodec = $serviceFactory->create('services.web.mime-to-codec');

        foreach (
            $serviceFactory->create(
                'invocations.web.config.web-mime-to-codec'
            ) as $translation
        ) {
            $webMimeToCodec->register(
                $translation['left'],
                $translation['right']
            );
        }

        $inputFactory = $serviceFactory->create('services.web.input-factory');
        $input = $inputFactory->create(
            $this->server,
            $this->get,
            $this->post,
            $this->files,
            $this->cookies
        );

        $outputFactory = $serviceFactory->create('services.web.output-factory');
        $output = $outputFactory->create($input);

        $serviceManager->registerService('web-input', $input);
        $serviceManager->registerService('web-output', $output);

        $router = $serviceFactory->create('services.web.router.base');
        try {
            $router->accepts($input, $output);
            $router($input, $output);
        } catch (Throwable $exception) {
            $errorHandler = $serviceFactory->create(
                'services.web.error-handler'
            );
            try {
                $errorHandler->outputByException($exception);
            } catch (NotAcceptedException $notAcceptedException) {
                $default = $serviceFactory->create(
                    'parameters.default-output-content-type'
                );
                $output->setContentType($default);
                foreach ($output->getAcceptedContentTypes() as $contentType) {
                    if (fnmatch($contentType, $default)) {
                        $errorHandler->outputByException($exception);

                        return;
                    }
                }

                $errorHandler->outputByException($notAcceptedException);
            }
        }
    }
}
