<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ncc\ThirdParty\Symfony\Uid\Factory;

use ncc\ThirdParty\Symfony\Uid\UuidV4;

class RandomBasedUuidFactory
{
    /**
     * @param class-string $class
     */
    public function __construct(
        private string $class,
    ) {
    }

    public function create(): UuidV4
    {
        $class = $this->class;

        return new $class();
    }
}
