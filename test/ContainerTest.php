<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection;

use Exception;
use N7e\DependencyInjection\Fixtures\A;
use N7e\DependencyInjection\Fixtures\AbstractClass;
use N7e\DependencyInjection\Fixtures\B;
use N7e\DependencyInjection\Fixtures\C;
use N7e\DependencyInjection\Fixtures\CircularDependency;
use N7e\DependencyInjection\Fixtures\Remaining;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
#[CoversClass(Inject::class)]
class ContainerTest extends TestCase
{
    private array $dependencies;
    private Container $container;
    private string $value;

    #[Before]
    public function setUp(): void
    {
        $this->dependencies = [
            new DependencyDefinition(
                'value',
                fn() => $this->value,
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ];
        $this->container = new Container($this->dependencies);
        $this->value = 'value';
    }

    #[Test]
    public function shouldDetermineWhetherHasValueForIdentifier(): void
    {
        $this->assertFalse((new Container([]))->has('missing'));
        $this->assertTrue($this->container->has('value'));
    }

    #[Test]
    public function shouldDetermineWhetherHasValueForAlias(): void
    {
        $this->assertFalse($this->container->has('v'));

        current(
            array_filter($this->dependencies, static fn($dependency) => $dependency->identifier === 'value')
        )->alias('v');

        $this->assertTrue($this->container->has('v'));
    }

    #[Test]
    public function shouldThrowExceptionIfIdentifierIsNotFound(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->get('missing');
    }

    #[Test]
    public function shouldThrowExceptionIfParameterIsUnresolvable(): void
    {
        $this->expectException(NotFoundException::class);

        $this->container->invoke(static fn(string $value) => $value);
    }

    #[Test]
    public function shouldProduceValueForIdentifier(): void
    {
        $this->assertEquals($this->value, $this->container->get('value'));
    }

    #[Test]
    public function shouldProduceValueForAlias(): void
    {
        current(
            array_filter($this->dependencies, static fn($dependency) => $dependency->identifier === 'value')
        )->alias('v');

        $this->assertEquals($this->value, $this->container->get('v'));
    }

    #[Test]
    public function shouldUseDefaultValueIfAvailable(): void
    {
        $this->assertEquals(
            'default',
            $this->container->invoke(static fn(#[Inject('value')] string $value = 'default') => $value)
        );
    }

    #[Test]
    public function shouldThrowExceptionIfCircularDependency(): void
    {
        $this->expectException(CircularReferenceException::class);

        $container = new Container([
            new DependencyDefinition(
                'circularDependency',
                static fn($container, $parameters) => $container->get('circularDependency', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);

        $container->get('circularDependency');
    }

    #[Test]
    public function shouldThrowExceptionIfCircularDependencyWhenConstructing(): void
    {
        $this->expectException(CircularReferenceException::class);

        $this->container->construct(CircularDependency::class);
    }

    #[Test]
    public function shouldThrowExceptionIfIndirectCircularDependency(): void
    {
        $this->expectException(CircularReferenceException::class);

        $container = new Container([
            new DependencyDefinition(
                'indirectCircularOne',
                static fn($container, $parameters) => $container->get('indirectCircularTwo', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            new DependencyDefinition(
                'indirectCircularTwo',
                static fn($container, $parameters) => $container->get('indirectCircularOne', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);

        $container->get('indirectCircularOne');
    }

    #[Test]
    public function shouldClearDependencyStackAppropriately(): void
    {
        $container = new Container([
            new DependencyDefinition(
                A::class,
                static fn($container) => $container->construct(A::class),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            (new DependencyDefinition(
                B::class,
                static fn($container) => $container->construct(B::class),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ))->singleton()
        ]);

        try {
            $container->get(B::class);

            $this->fail('Lifetime exception should be thrown');
        } catch (Exception $exception) {
            $this->assertInstanceOf(LifetimeViolationException::class, $exception);
        }

        try {
            $container->get(B::class);

            $this->fail('Lifetime exception should be thrown');
        } catch (Exception $exception) {
            $this->assertInstanceOf(LifetimeViolationException::class, $exception);
        }
    }

    #[Test]
    public function shouldThrowExceptionIfClassIsNotFound(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('does not exist');

        $this->container->construct('NonExistingClass');
    }

    #[Test]
    public function shouldThrowExceptionIfClassIsNotConstructible(): void
    {
        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->construct(AbstractClass::class);
    }

    #[Test]
    public function shouldConstructClassIfPossible(): void
    {
        $this->assertInstanceOf(A::class, (new Container([]))->construct(A::class));
    }

    #[Test]
    public function shouldPassGivenParametersToConstructor(): void
    {
        $a = new A();
        $b = new B($a);
        $c = $this->container->construct(C::class, $b);

        $this->assertInstanceOf(C::class, $c);
        $this->assertSame($b, $c->b);
        $this->assertSame($a, $c->b->a);
    }

    #[Test]
    public function shouldResolveDependenciesFromContainer(): void
    {
        $a = new A();
        $b = new B($a);
        $container = new Container([
            new DependencyDefinition(
                A::class,
                static fn() => $a,
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            new DependencyDefinition(
                B::class,
                static fn() => $b,
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);
        $c = $container->construct(C::class);

        $this->assertInstanceOf(C::class, $c);
        $this->assertSame($b, $c->b);
        $this->assertSame($a, $c->b->a);
    }

    #[Test]
    public function shouldResolveRemainingDependenciesFromContainer(): void
    {
        $first = 'first';
        $remaining = $this->container->construct(Remaining::class, $first);

        $this->assertEquals($first, $remaining->first);
        $this->assertInstanceOf(A::class, $remaining->a);
    }

    #[Test]
    public function shouldInvokeFunction(): void
    {
        $called = false;

        $this->container->invoke(function () use (&$called) {
            $called = true;
        });

        $this->assertTrue($called);
    }

    #[Test]
    public function shouldReturnInvokedFunctionReturnValue(): void
    {
        $this->assertEquals($this->value, $this->container->invoke(fn() => $this->value));
    }

    #[Test]
    public function shouldResolveFunctionParameters(): void
    {
        $this->assertInstanceOf(C::class, $this->container->invoke(static fn(C $c) => $c));
    }

    #[Test]
    public function shouldResolveValueByAttributeIdentifier(): void
    {
        $this->assertEquals(
            $this->value,
            $this->container->invoke(static fn(#[Inject('value')] string $value) => $value)
        );
    }

    #[Test]
    public function shouldCreateTransientDependenciesWhenRequested(): void
    {
        $container = new Container([
            new DependencyDefinition(
                A::class,
                static fn() => new A(),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);

        $this->assertNotSame($container->get(A::class), $container->get(A::class));
    }

    #[Test]
    public function shouldCreateSingletonDependenciesOnce(): void
    {
        $container = new Container([
            (new DependencyDefinition(
                A::class,
                static fn() => new A(),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ))->singleton()
        ]);
        $a = $container->get(A::class);

        $this->assertSame($a, $container->get(A::class));

        $container->beginScope();

        $this->assertSame($a, $container->get(A::class));
    }

    #[Test]
    public function shouldCreateScopedDependenciesOncePerScope(): void
    {
        $container = new Container([
            (new DependencyDefinition(
                A::class,
                static fn() => new A(),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ))->scoped()
        ]);
        $a = $container->get(A::class);

        $this->assertSame($a, $container->get(A::class));

        $container->beginScope();

        $newScopeA = $container->get(A::class);

        $this->assertSame($newScopeA, $container->get(A::class));
        $this->assertNotSame($a, $newScopeA);
    }

    #[Test]
    public function shouldThrowExceptionIfDependencyHasShorterLifetime(): void
    {
        $this->expectException(LifetimeViolationException::class);

        $container = new Container([
            (new DependencyDefinition(
                A::class,
                static fn($container) => $container->construct(A::class),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ))->transient(),
            (new DependencyDefinition(
                B::class,
                static fn($container) => $container->construct(B::class),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ))->singleton()
        ]);

        $container->get(B::class);
    }
}
