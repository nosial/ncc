<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Scalar\MagicConst;

use ncc\ThirdParty\nikic\PhpParser\Node\Scalar\MagicConst;

class Line extends MagicConst {
    public function getName(): string {
        return '__LINE__';
    }

    public function getType(): string {
        return 'Scalar_MagicConst_Line';
    }
}
