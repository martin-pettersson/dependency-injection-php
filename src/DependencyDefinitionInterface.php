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
 * Represents a dependency definition.
 *
 * A dependency definition can be modified after it has been created by the
 * {@see \N7e\DependencyInjection\ContainerBuilderInterface}.
 */
interface DependencyDefinitionInterface
{
    /**
     * Make the dependency lifetime transient.
     *
     * A transient dependency will be created everytime it is requested.
     *
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface Same instance for method chaining.
     */
    public function transient(): DependencyDefinitionInterface;

    /**
     * Make the dependency lifetime scoped.
     *
     * A scoped dependency will be created only once per scope. The lifetime of
     * a scope is up to the user of the dependency injection container.
     *
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface Same instance for method chaining.
     */
    public function scoped(): DependencyDefinitionInterface;

    /**
     * Make the dependency lifetime that of a singleton.
     *
     * A singleton dependency will only be created once.
     *
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface Same instance for method chaining.
     */
    public function singleton(): DependencyDefinitionInterface;

    /**
     * Add an alias to the dependency identifier.
     *
     * The dependency can be requested with the alias as if it was the
     * identifier.
     *
     * @param string $alias Arbitrary alias.
     * @return \N7e\DependencyInjection\DependencyDefinitionInterface Same instance for method chaining.
     * @throws \N7e\DependencyInjection\DuplicateIdentifierException
     *     If a dependency for the given alias is already configured.
     */
    public function alias(string $alias): DependencyDefinitionInterface;
}
