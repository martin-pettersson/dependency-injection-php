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
 * Has the ability to build dependency injection container instances.
 */
interface ContainerBuilderInterface
{
    /**
     * Configures a class dependency.
     *
     * @param string $class_name Fully qualified class name.
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface
     *     A reference to the configured dependency definition.
     * @throws \N7e\DependencyInjection\DuplicateDependencyIdentifierException
     *     If a dependency for the given class is already configured.
     */
    public function addClass(string $class_name): DependencyDefinitionInterface;

    /**
     * Configures a factory dependency for the given identifier.
     *
     * @param string $identifier Arbitrary dependency identifier.
     * @param callable $factory Arbitrary factory.
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface
     *     A reference to the configured dependency definition.
     * @throws \N7e\DependencyInjection\DuplicateDependencyIdentifierException
     *     If a dependency for the given identifier is already configured.
     */
    public function addFactory(string $identifier, callable $factory): DependencyDefinitionInterface;

    /**
     * Produce a configured dependency definition for a given identifier.
     *
     * @param string $identifier Arbitrary dependency identifier.
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface|null
     *     A reference to the configured dependency definition.
     */
    public function configure(string $identifier): ?DependencyDefinitionInterface;

    /**
     * Build a configured dependency injection container instance.
     *
     * @return \N7e\DependencyInjection\ContainerInterface Configured dependency injection container instance.
     */
    public function build(): ContainerInterface;
}
