<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node;

use ncc\ThirdParty\nikic\PhpParser\NodeAbstract;

/**
 * Represents the "..." in "foo(...)" of the first-class callable syntax.
 */
class VariadicPlaceholder extends NodeAbstract {
    /**
     * Create a variadic argument placeholder (first-class callable syntax).
     *
     * @param array<string, mixed> $attributes Additional attributes
     */
    public function __construct(array $attributes = []) {
        $this->attributes = $attributes;
    }

    public function getType(): string {
        return 'VariadicPlaceholder';
    }

    public function getSubNodeNames(): array {
        return [];
    }
}
