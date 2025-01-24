<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection;

use N7e\DependencyInjection\Fixtures\A;
use N7e\DependencyInjection\Fixtures\B;
use N7e\DependencyInjection\Fixtures\C;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Container::class)]
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
            ),
            new DependencyDefinition(
                'a',
                fn($container, $parameters) => $container->construct(A::class, ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            new DependencyDefinition(
                'b',
                fn($container, $parameters) => $container->construct(B::class, ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            new DependencyDefinition(
                'c',
                fn($container, $parameters) => $container->construct(C::class, ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ];
        $this->container = new Container($this->dependencies);
        $this->value = 'value';
    }

    #[Test]
    public function shouldDetermineWhetherHasValueForIdentifier(): void
    {
        $this->assertFalse($this->container->has('missing'));
        $this->assertTrue($this->container->has('value'));
    }

    #[Test]
    public function shouldDetermineWhetherHasValueForAlias(): void
    {
        current(
            array_filter($this->dependencies, fn($dependency) => $dependency->identifier() === 'a')
        )->alias('aa');

        $this->assertTrue($this->container->has('aa'));
    }

    #[Test]
    public function shouldDetermineWhetherHasValueForClass(): void
    {
        $this->assertFalse($this->container->has(A::class));
    }

    #[Test]
    public function shouldThrowIfIdentifierIsNotFound(): void
    {
        $this->expectException(DependencyNotFoundException::class);

        $this->container->get('missing');
    }

    #[Test]
    public function shouldProduceValueForIdentifier(): void
    {
        $this->assertInstanceOf(A::class, $this->container->get('a'));
    }

    #[Test]
    public function shouldProduceValueForAlias(): void
    {
        current(
            array_filter($this->dependencies, fn($dependency) => $dependency->identifier() === 'a')
        )->alias('aa');

        $this->assertInstanceOf(A::class, $this->container->get('aa'));
    }

    #[Test]
    public function shouldThrowIfCircularDependency(): void
    {
        $this->expectException(CircularDependencyReferenceException::class);

        $container = new Container([
            new DependencyDefinition(
                'circularDependency',
                fn($container, $parameters) => $container->get('circularDependency', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);

        $container->get('circularDependency');
    }

    #[Test]
    public function shouldThrowIfIndirectCircularDependency(): void
    {
        $this->expectException(CircularDependencyReferenceException::class);

        $container = new Container([
            new DependencyDefinition(
                'indirectCircularOne',
                fn($container, $parameters) => $container->construct('indirectCircularOne', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            ),
            new DependencyDefinition(
                'indirectCircularTwo',
                fn($container, $parameters) => $container->construct('indirectCircularTwo', ...$parameters),
                $this->getMockBuilder(ContainerBuilder::class)->getMock()
            )
        ]);

        $container->get('indirectCircularOne');
    }

    #[Test]
    public function shouldResolveValueByAttributeIdentifier(): void
    {
        $this->assertEquals(
            $this->value,
            $this->container->invoke(static fn(#[Inject('value')] string $value) => $value)
        );
    }
}
