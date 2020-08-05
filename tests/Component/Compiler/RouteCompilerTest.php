<?php

namespace Ulrack\WebApplication\Tests\Component\Compiler;

use PHPUnit\Framework\TestCase;
use GrizzIt\Cache\Common\CacheInterface;
use GrizzIt\Storage\Common\StorageInterface;
use GrizzIt\Storage\Component\ObjectStorage;
use GrizzIt\Configuration\Common\RegistryInterface;
use Ulrack\WebApplication\Component\Compiler\RouteCompiler;

/**
 * @coversDefaultClass \Ulrack\WebApplication\Component\Compiler\RouteCompiler
 */
class RouteCompilerTest extends TestCase
{
    /**
     * @covers ::compile
     * @covers ::compileFromStorage
     * @covers ::compileFromArray
     * @covers ::unpackToCache
     * @covers ::unpackRoute
     * @covers ::compileFromRegistry
     * @covers ::createRouteGroup
     * @covers ::createRoute
     * @covers ::isRouteReferenced
     * @covers ::__construct
     *
     * @param array $result
     * @param bool $cached
     * @param StorageInterface $cachedResult
     *
     * @return void
     *
     * @dataProvider routeProvider
     */
    public function testCompile(
        array $result,
        bool $cached,
        StorageInterface $cachedResult,
        array $route,
        array $routeGroups
    ): void {
        $cache = $this->createMock(CacheInterface::class);
        $registry = $this->createMock(RegistryInterface::class);

        $subject = new RouteCompiler($cache);

        $cache->expects(static::once())
            ->method('exists')
            ->with('routes')
            ->willReturn($cached);

        $cache->method('fetch')
            ->with('routes')
            ->willReturn($cachedResult);

        $registry->method('get')
            ->withConsecutive(['route'], ['route-group'])
            ->willReturnOnConsecutiveCalls(
                $route,
                $routeGroups
            );

        $this->isType('array', $subject->compile($registry));
    }

    /**
     * @return array
     */
    public function routeProvider(): array
    {
        return [
            [
                [],
                false,
                new ObjectStorage(),
                [
                    [
                        'key' => 'default-home',
                        'path' => '/',
                        'service' => 'services.default-home',
                        'methods' => ['GET'],
                        'outputService' => 'services.output-handler',
                        'errorRegistryService' => '',
                        'authorizations' => ['foo']
                    ],
                    [
                        'key' => 'foo',
                        'path' => 'foo',
                        'service' => 'services.foo',
                        'methods' => ['GET'],
                        'outputService' => 'services.output-handler',
                        'errorRegistryService' => '',
                        'authorizations' => [],
                        'parent' => 'default-home'
                    ]
                ],
                [
                    [
                        'key' => 'default',
                        'ports' => [80],
                        'hosts' => ['example.com'],
                        'errorRegistryService' => 'ervices.web.errors.default.api.registry',
                        'authorizations' => ['bar'],
                        'route' => 'default-home'
                    ]
                ]
            ],
            [
                [],
                true,
                new ObjectStorage(
                    [
                        [
                            'ports' => [80],
                            'hosts' => ['example.com'],
                            'route' => [
                                'path' => '/',
                                'service' => 'services.default-home',
                                'methods' => ['GET'],
                                'outputService' => 'services.output-handler',
                                'errorRegistryService' => '',
                                'authorizations' => ['foo'],
                                'routes' => [
                                    [
                                        'path' => 'foo',
                                        'service' => 'services.foo-endpoint',
                                        'methods' => ['GET'],
                                        'outputService' => 'services.output-handler',
                                        'errorRegistryService' => '',
                                        'authorizations' => [],
                                        'routes' => []
                                    ]
                                ]
                            ],
                            'errorRegistryService' => 'ervices.web.errors.default.api.registry',
                            'authorizations' => ['bar']
                        ]
                    ]
                ),
                [],
                []
            ]
        ];
    }
}
