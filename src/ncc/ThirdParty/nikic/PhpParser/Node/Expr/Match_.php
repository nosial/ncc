<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Expr;

use ncc\ThirdParty\nikic\PhpParser\Node;
use ncc\ThirdParty\nikic\PhpParser\Node\MatchArm;

class Match_ extends Node\Expr
{
    /** @var Node\Expr */
    public $cond;
    /** @var MatchArm[] */
    public $arms;

    /**
     * @param MatchArm[] $arms
     */
    public function __construct(Node\Expr $cond, array $arms = [], array $attributes = []) {
        $this->attributes = $attributes;
        $this->cond = $cond;
        $this->arms = $arms;
    }

    public function getSubNodeNames() : array {
        return ['cond', 'arms'];
    }

    public function getType() : string {
        return 'Expr_Match';
    }
}
