<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\WebApplication\Component\Compiler;

use RuntimeException;
use Ulrack\Web\Component\Router\Route;
use GrizzIt\Cache\Common\CacheInterface;
use Ulrack\Web\Component\Router\RouteGroup;
use GrizzIt\Storage\Common\StorageInterface;
use GrizzIt\Storage\Component\ObjectStorage;
use Ulrack\Web\Common\Router\RouteInterface;
use Ulrack\Web\Common\Router\RouteGroupInterface;
use GrizzIt\Configuration\Common\RegistryInterface;
use Ulrack\WebApplication\Common\Compiler\RouteCompilerInterface;

class RouteCompiler implements RouteCompilerInterface
{
    /**
     * Contains the cache for the route.
     *
     * @var CacheInterface
     */
    private $cache;

    /**
     * Constructor.
     *
     * @param CacheInterface $cache
     */
    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Compiles the route configuration to routeable objects.
     *
     * @return array
     */
    public function compile(RegistryInterface $registry): array
    {
        if ($this->cache->exists('routes')) {
            return $this->compileFromStorage($this->cache->fetch('routes'));
        }

        $compiled = $this->compileFromRegistry($registry);
        $this->unpackToCache($compiled);

        return $compiled;
    }

    /**
     * Creates the routing objects from a storage.
     *
     * @param StorageInterface $storage
     *
     * @return array
     */
    private function compileFromStorage(StorageInterface $storage): array
    {
        $result = [];
        foreach ($storage as $entry) {
            $result[] = $this->createRouteGroup(
                $entry,
                $this->compileFromArray($entry['route'])
            );
        }

        return $result;
    }

    /**
     * Compiles a route from an array.
     *
     * @param array $route
     *
     * @return RouteInterface
     */
    private function compileFromArray(array $route): RouteInterface
    {
        $routes = [];
        foreach ($route['routes'] as $subRoute) {
            $routes[] = $this->compileFromArray($subRoute);
        }

        return $this->createRoute(
            $route,
            ...$routes
        );
    }

    /**
     * Unpacks the routing and stores it in the cache.
     *
     * @param array $routing
     *
     * @return void
     */
    private function unpackToCache(array $routing): void
    {
        $array = [];

        /** @var RouteGroupInterface $routeGroup */
        foreach ($routing as $key => $routeGroup) {
            $array[$key] = [
                'ports' => $routeGroup->getPorts(),
                'hosts' => $routeGroup->getHosts(),
                'route' => $this->unpackRoute($routeGroup->getRoute()),
                'errorRegistryService' => $routeGroup->getErrorRegistryService(),
                'authorizations' => $routeGroup->getAuthorizations()
            ];
        }

        $this->cache->store('routes', new ObjectStorage($array));
    }

    /**
     * Unpacks route objects to an array.
     *
     * @param RouteInterface $route
     *
     * @return array
     */
    private function unpackRoute(RouteInterface $route): array
    {
        $routes = $route->getRoutes();
        $unpackedRoutes = [];
        foreach ($routes as $subRoute) {
            $unpackedRoutes[] = $this->unpackRoute($subRoute);
        }

        return [
            'path' => $route->getPath(),
            'service' => $route->getService(),
            'methods' => $route->getMethods(),
            'outputService' => $route->getOutputHandlerService(),
            'errorRegistryService' => $route->getErrorRegistryService(),
            'authorizations' => $route->getAuthorizations(),
            'routes' => $unpackedRoutes
        ];
    }

    /**
     * Compiles the routes from the configuration registry.
     *
     * @param RegistryInterface $registry
     *
     * @return array
     */
    private function compileFromRegistry(RegistryInterface $registry): array
    {
        $routes = $registry->get('route');
        $routeGroups = $registry->get('route-group');
        $routeReferences = [];
        $finalRoutes = [];
        $depth = 0;

        while (count($routes) > 0) {
            foreach ($routes as $key => $route) {
                if (!$this->isRouteReferenced($route, $routes)) {
                    $subRoutes = [];
                    if (isset($routeReferences[$route['key']])) {
                        ksort($routeReferences[$route['key']]);
                        $subRoutes = array_merge(...$routeReferences[$route['key']]);
                    }

                    $newRoute = $this->createRoute(
                        $route,
                        ...$subRoutes
                    );

                    $routeWeight = $route['weight'] ?? 1000;
                    $finalRoutes[$routeWeight][$route['key']] = $newRoute;
                    if (!empty($route['parent'])) {
                        $routeReferences[$route['parent']][$routeWeight][] = $newRoute;
                    }

                    unset($routes[$key]);
                    continue;
                }
            }

            // Prevent an infinite loop.
            if ($depth >= 256) {
                throw new RuntimeException('Nesting of routes deeper than 256 limit');
            }
            $depth++;
        }


        ksort($finalRoutes);
        $finalRoutes = array_merge(...$finalRoutes);
        $returnGroups = [];
        foreach ($routeGroups as $routeGroup) {
            $returnGroups[$routeGroup['weight'] ?? 1000][] = $this
                ->createRouteGroup(
                    $routeGroup,
                    $finalRoutes[$routeGroup['route']]
                );
        }

        ksort($returnGroups);

        return array_merge(...$returnGroups);
    }

    /**
     * Creates a new route group based on the configuration.
     *
     * @param array $routeGroup
     * @param RouteInterface $homeRoute
     *
     * @return RouteGroupInterface
     */
    private function createRouteGroup(
        array $routeGroup,
        RouteInterface $homeRoute
    ): RouteGroupInterface {
        $newRouteGroup = new RouteGroup(
            $routeGroup['ports'],
            $routeGroup['hosts'],
            $homeRoute,
            $routeGroup['errorRegistryService'] ?? ''
        );

        foreach ($routeGroup['authorizations'] ?? [] as $authorization) {
            $newRouteGroup->addAuthorization($authorization);
        }

        return $newRouteGroup;
    }

    /**
     * Creates the route object.
     *
     * @param array $route
     * @param RouteInterface ...$routes
     *
     * @return RouteInterface
     */
    private function createRoute(
        array $route,
        RouteInterface ...$routes
    ): RouteInterface {
        $newRoute = new Route(
            $route['path'],
            $route['service'],
            $route['methods'],
            $route['outputService'] ?? '',
            $route['errorRegistryService'] ?? '',
            ...$routes
        );

        foreach ($route['authorizations'] ?? [] as $authorization) {
            $newRoute->addAuthorization($authorization);
        }

        return $newRoute;
    }

    /**
     * Determines whether a route has been referenced.
     *
     * @param array $route
     * @param array $routes
     *
     * @return bool
     */
    private function isRouteReferenced(array $route, array $routes): bool
    {
        foreach ($routes as $subRoute) {
            if (($subRoute['parent'] ?? '') === $route['key']) {
                return true;
            }
        }

        return false;
    }
}
