<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace N7e\DependencyInjection\Fixtures;

class CircularDependency
{
    public CircularDependency $circularDependency;

    public function __construct(CircularDependency $circularDependency)
    {
        $this->circularDependency = $circularDependency;
    }
}
