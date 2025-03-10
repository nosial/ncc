<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Scalar;

use ncc\ThirdParty\nikic\PhpParser\Node\Scalar;

abstract class MagicConst extends Scalar {
    /**
     * Constructs a magic constant node.
     *
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $attributes = []) {
        $this->attributes = $attributes;
    }

    public function getSubNodeNames(): array {
        return [];
    }

    /**
     * Get name of magic constant.
     *
     * @return string Name of magic constant
     */
    abstract public function getName(): string;
}
