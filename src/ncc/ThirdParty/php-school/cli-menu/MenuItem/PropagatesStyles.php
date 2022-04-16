<?php

declare(strict_types=1);

namespace ncc\PhpSchool\CliMenu\MenuItem;

use ncc\PhpSchool\CliMenu\CliMenu;

interface PropagatesStyles
{
    /**
     * Push the parents styles to any
     * child items or menus.
     */
    public function propagateStyles(CliMenu $parent) : void;
}
