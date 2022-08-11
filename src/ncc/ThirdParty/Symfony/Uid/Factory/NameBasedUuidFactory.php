<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ncc\ThirdParty\Symfony\uid\Factory;

use ncc\ThirdParty\Symfony\uid\Uuid;
use ncc\ThirdParty\Symfony\uid\UuidV3;
use ncc\ThirdParty\Symfony\uid\UuidV5;

class NameBasedUuidFactory
{
    private string $class;
    private Uuid $namespace;

    public function __construct(string $class, Uuid $namespace)
    {
        $this->class = $class;
        $this->namespace = $namespace;
    }

    public function create(string $name): UuidV5|UuidV3
    {
        switch ($class = $this->class) {
            case UuidV5::class: return Uuid::v5($this->namespace, $name);
            case UuidV3::class: return Uuid::v3($this->namespace, $name);
        }

        if (is_subclass_of($class, UuidV5::class)) {
            $uuid = Uuid::v5($this->namespace, $name);
        } else {
            $uuid = Uuid::v3($this->namespace, $name);
        }

        return new $class($uuid);
    }
}
