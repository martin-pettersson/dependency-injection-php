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
 * Dependency injection container builder implementation.
 */
class ContainerBuilder implements ContainerBuilderInterface
{
    /**
     * Configured dependencies.
     *
     * @var \N7e\DependencyInjection\DependencyDefinition[]
     */
    private array $dependencies;

    /**
     * Create a new builder instance.
     */
    public function __construct()
    {
        $this->dependencies = [];
    }

    /** {@inheritDoc} */
    public function addClass(string $className): DependencyDefinitionInterface
    {
        $this->assertAvailabilityOf($className);

        $dependency = new DependencyDefinition(
            $className,
            static fn($container, $parameters) => $container->construct($className, ...$parameters),
            $this
        );

        $this->dependencies[] = $dependency;

        return $dependency;
    }

    /** {@inheritDoc} */
    public function addFactory(string $identifier, callable $factory): DependencyDefinitionInterface
    {
        $this->assertAvailabilityOf($identifier);

        $dependency = new DependencyDefinition(
            $identifier,
            static fn($container, $parameters) => $container->invoke($factory, ...$parameters),
            $this
        );

        $this->dependencies[] = $dependency;

        return $dependency;
    }

    /** {@inheritDoc} */
    public function configure(string $identifier): ?DependencyDefinitionInterface
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency->identifier === $identifier) {
                return $dependency;
            }
        }

        return null;
    }

    /** {@inheritDoc} */
    public function build(): ContainerInterface
    {
        return new Container($this->dependencies);
    }

    /**
     * Assert the availability of a given identifier.
     *
     * @param string $identifier Arbitrary identifier.
     * @throws \N7e\DependencyInjection\DuplicateIdentifierException
     *     If a dependency with the given identifier is already configured.
     */
    public function assertAvailabilityOf(string $identifier): void
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency->identifier === $identifier || in_array($identifier, $dependency->aliases(), true)) {
                throw new DuplicateIdentifierException($identifier);
            }
        }
    }
}
