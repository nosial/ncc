<?php declare(strict_types=1);

namespace ncc\ThirdParty\nikic\PhpParser\Lexer\TokenEmulator;

use ncc\ThirdParty\nikic\PhpParser\Lexer\Emulative;

final class MatchTokenEmulator extends KeywordEmulator
{
    public function getPhpVersion(): string
    {
        return Emulative::PHP_8_0;
    }

    public function getKeywordString(): string
    {
        return 'match';
    }

    public function getKeywordToken(): int
    {
        return \T_MATCH;
    }
}
