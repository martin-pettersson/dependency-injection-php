<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection;

use Attribute;

/**
 * Decorates parameters with a given dependency identifier.
 *
 * Use this when you need the container to resolve a dependency with a specific
 * identifier.
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
class Inject
{
    /**
     * Dependency identifier.
     *
     * @var string
     */
    private string $identifier;

    /**
     * Create a new attribute instance.
     *
     * @param string $identifier Arbitrary dependency identifier.
     */
    public function __construct(string $identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Retrieve dependency identifier.
     *
     * @return string Dependency identifier.
     */
    public function identifier(): string
    {
        return $this->identifier;
    }
}
