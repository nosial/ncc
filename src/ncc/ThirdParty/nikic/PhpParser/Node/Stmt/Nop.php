<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Stmt;

use ncc\ThirdParty\nikic\PhpParser\Node;

/** Nop/empty statement (;). */
class Nop extends Node\Stmt {
    public function getSubNodeNames(): array {
        return [];
    }

    public function getType(): string {
        return 'Stmt_Nop';
    }
}
