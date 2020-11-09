<?php

namespace Ulrack\WebApplication\Tests\Component\Application;

use Exception;
use PHPUnit\Framework\TestCase;
use Ulrack\Web\Factory\InputFactory;
use Ulrack\Web\Factory\OutputFactory;
use Ulrack\Web\Common\Router\RouterInterface;
use Ulrack\Web\Exception\NotAcceptedException;
use Ulrack\Web\Common\Endpoint\OutputInterface;
use Ulrack\Web\Common\Error\ErrorHandlerInterface;
use Ulrack\Services\Common\ServiceFactoryInterface;
use GrizzIt\Translator\Component\MatchingArrayTranslator;
use Ulrack\Kernel\Common\Manager\ServiceManagerInterface;
use Ulrack\WebApplication\Component\Application\WebApplication;

/**
 * @coversDefaultClass \Ulrack\WebApplication\Component\Application\WebApplication
 */
class WebApplicationTest extends TestCase
{
    /**
     * @covers ::run
     * @covers ::__construct
     *
     * @return void
     */
    public function testRun(): void
    {
        $server = [];
        $get = [];
        $post = [];
        $files = [];
        $cookies = [];
        $subject = new WebApplication($server, $get, $post, $files, $cookies);

        $serviceManager = $this->createMock(ServiceManagerInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $router = $this->createMock(RouterInterface::class);

        $serviceManager->method('getServiceFactory')
            ->willReturn($serviceFactory);

        $serviceFactory->expects(self::exactly(5))
            ->method('create')
            ->withConsecutive(
                ['services.web.mime-to-codec'],
                ['invocations.web.config.web-mime-to-codec'],
                ['services.web.input-factory'],
                ['services.web.output-factory'],
                ['services.web.group-router']
            )->willReturnOnConsecutiveCalls(
                $this->createMock(MatchingArrayTranslator::class),
                [['left' => ['application/json'], 'right' => ['json']]],
                $this->createMock(InputFactory::class),
                $this->createMock(OutputFactory::class),
                $router
            );

        $router->expects(static::once())
            ->method('__invoke');

        $subject->run($serviceManager);
    }

    /**
     * @covers ::run
     * @covers ::__construct
     *
     * @return void
     */
    public function testRunFailure(): void
    {
        $server = [];
        $get = [];
        $post = [];
        $files = [];
        $cookies = [];
        $subject = new WebApplication($server, $get, $post, $files, $cookies);

        $serviceManager = $this->createMock(ServiceManagerInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);

        $serviceManager->method('getServiceFactory')
            ->willReturn($serviceFactory);

        $serviceFactory->expects(self::exactly(6))
            ->method('create')
            ->withConsecutive(
                ['services.web.mime-to-codec'],
                ['invocations.web.config.web-mime-to-codec'],
                ['services.web.input-factory'],
                ['services.web.output-factory'],
                ['services.web.group-router'],
                ['services.web.error-handler']
            )->willReturnOnConsecutiveCalls(
                $this->createMock(MatchingArrayTranslator::class),
                [['left' => ['application/json'], 'right' => ['json']]],
                $this->createMock(InputFactory::class),
                $this->createMock(OutputFactory::class),
                $router,
                $errorHandler
            );

        $exception = new Exception();

        $router->expects(static::once())
            ->method('__invoke')
            ->willThrowException($exception);

        $errorHandler->expects(static::once())
            ->method('outputByException')
            ->with($exception);

        $subject->run($serviceManager);
    }

    /**
     * @covers ::run
     * @covers ::__construct
     *
     * @return void
     */
    public function testRunFailureNotAccepted(): void
    {
        $server = [];
        $get = [];
        $post = [];
        $files = [];
        $cookies = [];
        $subject = new WebApplication($server, $get, $post, $files, $cookies);

        $serviceManager = $this->createMock(ServiceManagerInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);

        $serviceManager->method('getServiceFactory')
            ->willReturn($serviceFactory);

        $serviceFactory->expects(self::exactly(7))
            ->method('create')
            ->withConsecutive(
                ['services.web.mime-to-codec'],
                ['invocations.web.config.web-mime-to-codec'],
                ['services.web.input-factory'],
                ['services.web.output-factory'],
                ['services.web.group-router'],
                ['services.web.error-handler'],
                ['parameters.default-output-content-type']
            )->willReturnOnConsecutiveCalls(
                $this->createMock(MatchingArrayTranslator::class),
                [['left' => ['application/json'], 'right' => ['json']]],
                $this->createMock(InputFactory::class),
                $this->createMock(OutputFactory::class),
                $router,
                $errorHandler,
                'text/plain'
            );

        $exception = new Exception();
        $notAccepted = new NotAcceptedException();

        $router->expects(static::once())
            ->method('__invoke')
            ->willThrowException($exception);

        $errorHandler->expects(static::exactly(2))
            ->method('outputByException')
            ->withConsecutive([$exception], [$notAccepted])
            ->willReturnOnConsecutiveCalls(
                $this->throwException($notAccepted),
                null
            );

        $subject->run($serviceManager);
    }

    /**
     * @covers ::run
     * @covers ::__construct
     *
     * @return void
     */
    public function testRunFailureFirstAccepted(): void
    {
        $server = [];
        $get = [];
        $post = [];
        $files = [];
        $cookies = [];
        $subject = new WebApplication($server, $get, $post, $files, $cookies);

        $serviceManager = $this->createMock(ServiceManagerInterface::class);
        $serviceFactory = $this->createMock(ServiceFactoryInterface::class);
        $router = $this->createMock(RouterInterface::class);
        $errorHandler = $this->createMock(ErrorHandlerInterface::class);
        $outputFactory = $this->createMock(OutputFactory::class);
        $output = $this->createMock(OutputInterface::class);

        $outputFactory->expects(static::once())
            ->method('create')
            ->willReturn($output);

        $serviceManager->method('getServiceFactory')
            ->willReturn($serviceFactory);

        $serviceFactory->expects(self::exactly(7))
            ->method('create')
            ->withConsecutive(
                ['services.web.mime-to-codec'],
                ['invocations.web.config.web-mime-to-codec'],
                ['services.web.input-factory'],
                ['services.web.output-factory'],
                ['services.web.group-router'],
                ['services.web.error-handler'],
                ['parameters.default-output-content-type']
            )->willReturnOnConsecutiveCalls(
                $this->createMock(MatchingArrayTranslator::class),
                [['left' => ['application/json'], 'right' => ['json']]],
                $this->createMock(InputFactory::class),
                $outputFactory,
                $router,
                $errorHandler,
                'text/plain'
            );

        $exception = new Exception();
        $notAccepted = new NotAcceptedException();

        $output->expects(static::once())
            ->method('getAcceptedContentTypes')
            ->willReturn(['text/plain']);

        $router->expects(static::once())
            ->method('__invoke')
            ->willThrowException($exception);

        $errorHandler->expects(static::exactly(2))
            ->method('outputByException')
            ->withConsecutive([$exception], [$exception])
            ->willReturnOnConsecutiveCalls(
                $this->throwException($notAccepted),
                null
            );

        $subject->run($serviceManager);
    }
}
