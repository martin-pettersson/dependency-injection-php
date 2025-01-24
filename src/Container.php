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
     * Stack of dependencies being resolved.
     *
     * @var string[]
     */
    private array $dependencyStack;

    /**
     * Create a new container instance.
     *
     * @param \N7e\DependencyInjection\DependencyDefinition[] $dependencies Configured dependencies.
     */
    public function __construct(array $dependencies)
    {
        $this->dependencies = $dependencies;
        $this->dependencyStack = [];
    }

    /** {@inheritDoc} */
    public function has($id): bool
    {
        return ! is_null($this->dependencyFor($id));
    }

    /** {@inheritDoc} */
    public function get($id)
    {
        $dependency = $this->dependencyFor($id);

        if (is_null($dependency)) {
            throw new DependencyNotFoundException($id);
        }

        if (in_array($dependency->identifier(), $this->dependencyStack, true)) {
            throw new CircularDependencyReferenceException($dependency->identifier());
        }

        $this->dependencyStack[] = $dependency->identifier();

        $value = $this->invoke($dependency->factory(), $this, []);

        array_pop($this->dependencyStack);

        return $value;
    }

    /** {@inheritDoc} */
    public function construct(string $className, ...$parameters)
    {
        if (in_array($className, $this->dependencyStack, true)) {
            throw new CircularDependencyReferenceException($className);
        }

        $this->dependencyStack[] = $className;

        $reflection = new ReflectionClass($className);

        if (! $reflection->isInstantiable()) {
            // TODO Throw relevant exception.
        }

        $constructor = $reflection->getConstructor();

        $instance = is_null($constructor) ?
            $reflection->newInstance() :
            $reflection->newInstanceArgs($this->resolveParametersFor($constructor, $parameters));

        array_pop($this->dependencyStack);

        return $instance;
    }

    /** {@inheritDoc} */
    public function invoke(callable $callable, ...$parameters)
    {
        $reflection = new ReflectionFunction($callable(...));

        if ($reflection->getNumberOfParameters() === 0) {
            return $reflection->invoke();
        }

        return $reflection->invokeArgs($this->resolveParametersFor($reflection, $parameters));
    }

    /**
     * Find a dependency for a given identifier.
     *
     * @param string $identifier Arbitrary identifier.
     * @return \N7e\DependencyInjection\DependencyDefinition|null Dependency definition instance if found.
     */
    private function dependencyFor(string $identifier): ?DependencyDefinition
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
     */
    private function resolveParametersFor(ReflectionFunctionAbstract $callable, array $parameters): array
    {
        if ($callable->getNumberOfParameters() === count($parameters)) {
            return $parameters;
        }

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
     * @throws \N7e\DependencyInjection\DependencyNotFoundException
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
         * @var \ReflectionAttribute|false $attribute
         */
        $attribute = current($parameter->getAttributes(Inject::class));

        if ($attribute !== false) {
            return $this->get($attribute->newInstance()->identifier());
        }

        $type = $parameter->getType();

        if (is_null($type) || ($type instanceof ReflectionNamedType && $type->isBuiltin())) {
            throw new DependencyNotFoundException("{$parameter->getType()} \${$parameter->getName()}");
        }

        return $this->get((string) $type);
    }
}
