<?php
declare(strict_types=1);

namespace ncc\PhpSchool\CliMenu\Action;

use ncc\PhpSchool\CliMenu\CliMenu;

/**
 * @author Aydin Hassan <aydin@hotmail.co.uk>
 */
class ExitAction
{
    public function __invoke(CliMenu $menu) : void
    {
        $menu->close();
    }
}
