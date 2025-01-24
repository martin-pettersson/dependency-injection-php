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
 * Dependency definition implementation.
 */
class DependencyDefinition implements DependencyDefinitionInterface
{
    /**
     * Unique identifier.
     *
     * @var string
     */
    private string $identifier;

    /**
     * Value factory.
     *
     * @var callable
     */
    private $factory;

    /**
     * Container builder instance.
     *
     * @var \N7e\DependencyInjection\ContainerBuilder
     */
    private ContainerBuilder $containerBuilder;

    /**
     * Lifetime of the dependency in the container {@see \N7e\DependencyInjection\DependencyLifetime}.
     *
     * @var int
     */
    private int $lifetime;

    /**
     * Set of configured aliases.
     *
     * @var string[]
     */
    private array $aliases;

    /**
     * Create a new dependency definition instance.
     *
     * @param string $identifier Unique identifier.
     * @param callable $factory Value factory.
     * @param \N7e\DependencyInjection\ContainerBuilder $containerBuilder Container builder instance.
     */
    public function __construct(string $identifier, callable $factory, ContainerBuilder $containerBuilder)
    {
        $this->identifier = $identifier;
        $this->factory = $factory;
        $this->containerBuilder = $containerBuilder;
        $this->lifetime = DependencyLifetime::TRANSIENT;
        $this->aliases = [];
    }

    /** {@inheritDoc} */
    public function transient(): DependencyDefinitionInterface
    {
        $this->lifetime = DependencyLifetime::TRANSIENT;

        return $this;
    }

    /** {@inheritDoc} */
    public function scoped(): DependencyDefinitionInterface
    {
        $this->lifetime = DependencyLifetime::SCOPED;

        return $this;
    }

    /** {@inheritDoc} */
    public function singleton(): DependencyDefinitionInterface
    {
        $this->lifetime = DependencyLifetime::SINGLETON;

        return $this;
    }

    /** {@inheritDoc} */
    public function alias(string $alias): DependencyDefinitionInterface
    {
        $this->containerBuilder->assertAvailabilityOf($alias);

        $this->aliases[] = $alias;

        return $this;
    }

    /**
     * Retrieve the dependency identifier.
     *
     * @return string Unique identifier.
     */
    public function identifier(): string
    {
        return $this->identifier;
    }

    /**
     * Retrieve the dependency value factory.
     *
     * @return callable Value factory.
     */
    public function factory(): callable
    {
        return $this->factory;
    }

    /**
     * Retrieve the dependency value factory.
     *
     * @return int Value factory {@see \N7e\DependencyInjection\DependencyLifetime}.
     */
    public function lifetime(): int
    {
        return $this->lifetime;
    }

    /**
     * Retrieve the dependency aliases.
     *
     * @return string[] Set of aliases.
     */
    public function aliases(): array
    {
        return $this->aliases;
    }
}
