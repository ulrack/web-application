<?php

/**
 * Copyright (C) GrizzIT, Inc. All rights reserved.
 * See LICENSE for license details.
 */

namespace Ulrack\WebApplication\Common\Compiler;

use GrizzIt\Configuration\Common\RegistryInterface;

interface RouteCompilerInterface
{
    /**
     * Compiles the route configuration to routeable objects.
     *
     * @return array
     */
    public function compile(RegistryInterface $registry): array;
}
