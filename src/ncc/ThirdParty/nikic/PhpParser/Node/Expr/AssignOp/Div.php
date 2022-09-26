<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Expr\AssignOp;

use ncc\ThirdParty\nikic\PhpParser\Node\Expr\AssignOp;

class Div extends AssignOp
{
    public function getType() : string {
        return 'Expr_AssignOp_Div';
    }
}
