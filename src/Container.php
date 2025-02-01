<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection;

use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Dependency injection container implementation.
 */
class Container implements ContainerInterface
{
    /**
     * Configured dependencies.
     *
     * @var \N7e\DependencyInjection\DependencyDefinition[]
     */
    private array $dependencies;

    /**
     * Produced singleton dependency values.
     *
     * @var array
     */
    private array $singletonDependencyValues;

    /**
     * Produced scoped dependency values.
     *
     * @var array
     */
    private array $scopedDependencyValues;

    /**
     * Stack of dependencies being resolved using `::get()`.
     *
     * @var string[]
     */
    private array $getDependencyStack;

    /**
     * Stack of dependencies being resolved using `::construct()`.
     *
     * @var string[]
     */
    private array $constructDependencyStack;

    /**
     * Create a new container instance.
     *
     * @param \N7e\DependencyInjection\DependencyDefinition[] $dependencies Configured dependencies.
     */
    public function __construct(array $dependencies)
    {
        $this->dependencies = $dependencies;
        $this->getDependencyStack = [];
        $this->constructDependencyStack = [];
        $this->singletonDependencyValues = [];
        $this->scopedDependencyValues = [];
    }

    /** {@inheritDoc} */
    public function has($id): bool
    {
        return ! is_null($this->dependencyDefinitionFor($id));
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        $definition = $this->dependencyDefinitionFor($id);

        if (is_null($definition)) {
            throw new NotFoundException("Dependency definition for '{$id}' not found");
        }

        if (in_array($definition->identifier(), $this->getDependencyStack, true)) {
            $this->getDependencyStack = [];

            throw new CircularReferenceException($definition->identifier());
        }

        if (! is_null($cachedValue = $this->cached($definition))) {
            return $cachedValue;
        }

        $this->ensureLifetimeExpectancyOf($definition);
        $this->getDependencyStack[] = $definition->identifier();

        try {
            $value = $this->invoke($definition->factory(), $this, []);

            $this->cache($value, $definition);

            return $value;
        } finally {
            array_pop($this->getDependencyStack);
        }
    }

    /** {@inheritDoc} */
    public function construct(string $className, ...$parameters)
    {
        if (in_array($className, $this->constructDependencyStack, true)) {
            $this->constructDependencyStack = [];

            throw new CircularReferenceException($className);
        }

        $this->constructDependencyStack[] = $className;

        try {
            $reflection = new ReflectionClass($className);

            if (! $reflection->isInstantiable()) {
                throw new ContainerException("{$className} is not instantiable");
            }

            $constructor = $reflection->getConstructor();

            return is_null($constructor) ?
                $reflection->newInstance() :
                $reflection->newInstanceArgs($this->resolveParametersFor($constructor, $parameters));
        } catch (ReflectionException $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        } finally {
            array_pop($this->constructDependencyStack);
        }
    }

    /** {@inheritDoc} */
    public function invoke(callable $callable, ...$parameters)
    {
        // The 'callable' type hint ensures that only existing functions can be passed.
        $reflection = new ReflectionFunction($callable(...));

        if ($reflection->getNumberOfParameters() === 0) {
            return $reflection->invoke();
        }

        return $reflection->invokeArgs($this->resolveParametersFor($reflection, $parameters));
    }

    /** {@inheritdoc} */
    public function beginScope(): void
    {
        $this->scopedDependencyValues = [];
    }

    /**
     * Find a dependency for a given identifier.
     *
     * @param string $identifier Arbitrary identifier.
     * @return \N7e\DependencyInjection\DependencyDefinition|null Dependency definition instance if found.
     */
    private function dependencyDefinitionFor(string $identifier): ?DependencyDefinition
    {
        foreach ($this->dependencies as $dependency) {
            if ($dependency->identifier() === $identifier || in_array($identifier, $dependency->aliases(), true)) {
                return $dependency;
            }
        }

        return null;
    }

    /**
     * Resolve missing parameters for a given callable reflection.
     *
     * @param \ReflectionFunctionAbstract $callable Arbitrary callable reflection.
     * @param array $parameters Known parameters.
     * @return array Resolved parameters.
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function resolveParametersFor(ReflectionFunctionAbstract $callable, array $parameters): array
    {
        return array_map(
            fn($parameter) => array_key_exists($parameter->getPosition(), $parameters) ?
                $parameters[$parameter->getPosition()] :
                $this->resolve($parameter),
            $callable->getParameters()
        );
    }

    /**
     * Resolve a value for a given parameter.
     *
     * @param \ReflectionParameter $parameter Arbitrary parameter reflection.
     * @return mixed Parameter value.
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    private function resolve(ReflectionParameter $parameter)
    {
        if ($parameter->isOptional()) {
            return $parameter->getDefaultValue();
        }

        /**
         * Parameter inject attribute reflection instance.
         *
         * @var \ReflectionAttribute<\N7e\DependencyInjection\Inject>|false $attribute
         */
        $attribute = current($parameter->getAttributes(Inject::class));

        if ($attribute !== false) {
            return $this->get($attribute->newInstance()->identifier());
        }

        $type = $parameter->getType();

        if (is_null($type) || ($type instanceof ReflectionNamedType && $type->isBuiltin())) {
            throw new NotFoundException("Value for parameter '\${$parameter->getName()}' could not be resolved");
        }

        $identifier = (string) $type;

        return $this->has($identifier) ? $this->get($identifier) : $this->construct($identifier);
    }

    /**
     * Ensure lifetime expectancy of a given dependency definition.
     *
     * This ensures that a singleton cannot have transient dependencies. That
     * would defeat the purpose of a transient dependency.
     *
     * @param \N7e\DependencyInjection\DependencyDefinition $definition Arbitrary dependency definition.
     * @throws \N7e\DependencyInjection\LifetimeViolationException
     */
    private function ensureLifetimeExpectancyOf(DependencyDefinition $definition): void
    {
        foreach ($this->getDependencyStack as $identifier) {
            if ($definition->lifetime() < $this->dependencyDefinitionFor($identifier)?->lifetime()) {
                $this->getDependencyStack = [];

                throw new LifetimeViolationException($definition->identifier());
            }
        }
    }

    /**
     * Produce cached value if found.
     *
     * @param \N7e\DependencyInjection\DependencyDefinition $definition Arbitrary dependency definition.
     * @return mixed|null Cached value if found.
     */
    private function cached(DependencyDefinition $definition)
    {
        if (array_key_exists($definition->identifier(), $this->singletonDependencyValues)) {
            return $this->singletonDependencyValues[$definition->identifier()];
        }

        if (array_key_exists($definition->identifier(), $this->scopedDependencyValues)) {
            return $this->scopedDependencyValues[$definition->identifier()];
        }

        return null;
    }

    /**
     * Cache value if appropriate.
     *
     * @param mixed $value Arbitrary value.
     * @param \N7e\DependencyInjection\DependencyDefinition $definition Arbitrary dependency definition.
     */
    private function cache($value, DependencyDefinition $definition): void
    {
        if ($definition->lifetime() === DependencyLifetime::SINGLETON) {
            $this->singletonDependencyValues[$definition->identifier()] = $value;
        }

        if ($definition->lifetime() === DependencyLifetime::SCOPED) {
            $this->scopedDependencyValues[$definition->identifier()] = $value;
        }
    }
}
