<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection;

/**
 * Defined set of dependency lifetimes.
 *
 * This defines a dependency's lifetime in the container.
 */
class DependencyLifetime
{
    /**
     * Always created when requested.
     */
    public const TRANSIENT = 0;

    /**
     * Created only once per scope.
     */
    public const SCOPED = 1;

    /**
     * Only created once.
     */
    public const SINGLETON = 2;
}
