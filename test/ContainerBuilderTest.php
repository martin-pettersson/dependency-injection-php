<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace N7e\DependencyInjection;

use N7e\DependencyInjection\Fixtures\A;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ContainerBuilder::class)]
#[CoversClass(DependencyDefinition::class)]
class ContainerBuilderTest extends TestCase
{
    private ContainerBuilder $containerBuilder;
    private string $value;

    #[Before]
    public function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
        $this->value = 'value';
    }

    #[Test]
    public function shouldAddClassWithGivenIdentifier(): void
    {
        $this->containerBuilder->addClass(A::class);

        $this->assertTrue($this->containerBuilder->build()->has(A::class));
    }

    #[Test]
    public function shouldThrowExceptionIfDuplicateDependencyIdentifierWhenAddingClass(): void
    {
        $this->expectException(DuplicateIdentifierException::class);

        $this->containerBuilder->addClass(A::class);
        $this->containerBuilder->addClass(A::class);
    }

    #[Test]
    public function shouldAddValueFactoryWithGivenIdentifier(): void
    {
        $this->containerBuilder->addFactory('value', fn() => $this->value);

        $this->assertEquals($this->value, $this->containerBuilder->build()->get('value'));
    }

    #[Test]
    public function shouldThrowExceptionIfDuplicateDependencyIdentifierWhenAddingFactory(): void
    {
        $this->expectException(DuplicateIdentifierException::class);

        $this->containerBuilder->addFactory('value', fn() => $this->value);
        $this->containerBuilder->addFactory('value', fn() => $this->value);
    }

    #[Test]
    public function shouldReturnConfiguredDependencyDefinition(): void
    {
        $this->containerBuilder->addFactory('value', fn() => $this->value);

        $this->assertNotNull($this->containerBuilder->configure('value'));
    }

    #[Test]
    public function shouldReturnNullIfNoConfiguredDependencyIsFound(): void
    {
        $this->assertNull($this->containerBuilder->configure('value'));
    }

    #[Test]
    public function shouldAddDependencyAlias(): void
    {
        $this->containerBuilder->addFactory('value', fn() => $this->value)->alias('alias');

        $this->assertEquals($this->value, $this->containerBuilder->build()->get('alias'));
    }

    #[Test]
    public function shouldThrowExceptionIfDuplicateAliasIdentifier(): void
    {
        $this->expectException(DuplicateIdentifierException::class);

        $this->containerBuilder->addFactory('value', fn() => $this->value)->alias('value');
    }

    #[Test]
    public function shouldMakeDependencyTransient(): void
    {
        $this->containerBuilder->addClass(A::class)->transient();

        $container = $this->containerBuilder->build();

        $this->assertNotSame($container->get(A::class), $container->get(A::class));
    }

    #[Test]
    public function shouldMakeDependencyScoped(): void
    {
        $this->containerBuilder->addClass(A::class)->scoped();

        $container = $this->containerBuilder->build();
        $a = $container->get(A::class);

        $this->assertSame($container->get(A::class), $container->get(A::class));

        $container->beginScope();

        $newA = $container->get(A::class);

        $this->assertNotSame($a, $newA);
        $this->assertSame($newA, $container->get(A::class));
    }

    #[Test]
    public function shouldMakeDependencySingleton(): void
    {
        $this->containerBuilder->addClass(A::class)->singleton();

        $container = $this->containerBuilder->build();
        $a = $container->get(A::class);

        $this->assertSame($a, $container->get(A::class));

        $container->beginScope();

        $this->assertSame($a, $container->get(A::class));
    }

    #[Test]
    public function shouldBeTransientByDefault(): void
    {
        $this->containerBuilder->addClass(A::class);

        $container = $this->containerBuilder->build();

        $this->assertNotSame($container->get(A::class), $container->get(A::class));
    }
}
