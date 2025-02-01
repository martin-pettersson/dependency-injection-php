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
 * Represents a dependency injection container.
 *
 * A container instance is capable of producing identified values as well as
 * resolving dependencies for object construction and function invocations.
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Construct a given class resolving any required dependencies.
     *
     * @param string $className Fully qualified class name.
     * @param mixed ...$parameters
     *     Arbitrary set of parameters to pass to the constructor.
     * @return mixed Class instance.
     * @throws \N7e\DependencyInjection\CircularReferenceException
     *     When attempting to inject a dependency with a circular reference.
     * @throws \N7e\DependencyInjection\NotFoundException When a required dependency cannot be resolved.
     * @throws \N7e\DependencyInjection\LifetimeViolationException
     *     When attempting to inject a dependency with a shorter lifetime.
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function construct(string $className, ...$parameters);

    /**
     * Invoke a given callable resolving any required parameters.
     *
     * @param callable $callable Arbitrary callable.
     * @param mixed ...$parameters Arbitrary set of parameters to pass to the invokation.
     * @return mixed Callable invokation return value.
     * @throws \N7e\DependencyInjection\CircularReferenceException
     *     When attempting to inject a dependency with a circular reference.
     * @throws \N7e\DependencyInjection\NotFoundException When a required dependency cannot be resolved.
     * @throws \N7e\DependencyInjection\LifetimeViolationException
     *     When attempting to inject a dependency with a shorter lifetime.
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function invoke(callable $callable, ...$parameters);

    /**
     * Begin a new scope.
     *
     * This "flushes" any scoped dependencies so that if requested again they are recreated.
     */
    public function beginScope(): void;
}
