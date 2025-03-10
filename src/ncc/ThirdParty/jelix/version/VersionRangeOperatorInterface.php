<?php
/**
 * @author      Laurent Jouanneau
 * @copyright   2008-2020 Laurent Jouanneau
 *
 * @link        http://www.jelix.org
 * @licence     MIT
 */

namespace ncc\ThirdParty\jelix\Version;

interface VersionRangeOperatorInterface
{
    /**
    * @return bool
    */
    public function compare(Version $value);
}
