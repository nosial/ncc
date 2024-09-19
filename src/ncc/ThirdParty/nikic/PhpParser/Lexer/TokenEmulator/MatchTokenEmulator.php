<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Lexer\TokenEmulator;

use ncc\ThirdParty\nikic\PhpParser\PhpVersion;

final class MatchTokenEmulator extends KeywordEmulator {
    public function getPhpVersion(): PhpVersion {
        return PhpVersion::fromComponents(8, 0);
    }

    public function getKeywordString(): string {
        return 'match';
    }

    public function getKeywordToken(): int {
        return \T_MATCH;
    }
}
