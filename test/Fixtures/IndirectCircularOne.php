<?php

/*
 * Copyright (c) 2025 Martin Pettersson
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace N7e\DependencyInjection\Fixtures;

readonly class IndirectCircularOne
{
    public IndirectCircularTwo $indirectCircularTwo;

    public function __construct(IndirectCircularTwo $indirectCircularTwo)
    {
        $this->indirectCircularTwo = $indirectCircularTwo;
    }
}
