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

use ncc\ThirdParty\Symfony\Uid\Ulid;

class UlidFactory
{
    public function create(?\DateTimeInterface $time = null): Ulid
    {
        return new Ulid(null === $time ? null : Ulid::generate($time));
    }
}
