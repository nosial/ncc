<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Node\Scalar\MagicConst;

use ncc\ThirdParty\nikic\PhpParser\Node\Scalar\MagicConst;

class Property extends MagicConst {
    public function getName(): string {
        return '__PROPERTY__';
    }

    public function getType(): string {
        return 'Scalar_MagicConst_Property';
    }
}
