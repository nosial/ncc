<?php
declare(strict_types=1);

namespace ncc\PhpSchool\CliMenu\Terminal;

use ncc\PhpSchool\Terminal\IO\ResourceInputStream;
use ncc\PhpSchool\Terminal\IO\ResourceOutputStream;
use ncc\PhpSchool\Terminal\Terminal;
use ncc\PhpSchool\Terminal\UnixTerminal;

/**
 * @author Michael Woodward <mikeymike.mw@gmail.com>
 */
class TerminalFactory
{
    public static function fromSystem() : Terminal
    {
        return new UnixTerminal(new ResourceInputStream, new ResourceOutputStream);
    }
}
