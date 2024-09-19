<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Stmt;

use ncc\ThirdParty\nikic\PhpParser\Node;

abstract class TraitUseAdaptation extends Node\Stmt {
    /** @var Node\Name|null Trait name */
    public ?Node\Name $trait;
    /** @var Node\Identifier Method name */
    public Node\Identifier $method;
}
