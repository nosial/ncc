<?php
    /*
     * Copyright (c) Nosial 2022-2026, all rights reserved.
     *
     *  Permission is hereby granted, free of charge, to any person obtaining a copy of this software and
     *  associated documentation files (the "Software"), to deal in the Software without restriction, including without
     *  limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the
     *  Software, and to permit persons to whom the Software is furnished to do so, subject to the following
     *  conditions:
     *
     *  The above copyright notice and this permission notice shall be included in all copies or substantial portions
     *  of the Software.
     *
     *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
     *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
     *  PURPOSE AND NON-INFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
     *  LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
     *  OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
     *  DEALINGS IN THE SOFTWARE.
     *
     */

    namespace ncc\CLI;

    use ncc\Libraries\OptsLib\Parse;
    use PHPUnit\Framework\TestCase;

    class MainArgumentSplitTest extends TestCase
    {
        public function testArgumentsAfterArgsAreNotParsedByNcc(): void
        {
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['exec', '--package=com.phpunit.phpunit', '--args', '--version', '-h', '--build', '--project']);

            $this->assertSame(['exec', '--package=com.phpunit.phpunit'], $nccArguments);
            $this->assertSame(['--version', '-h', '--build', '--project'], $passthrough);

            $parsed = Parse::parseArgument($nccArguments);
            $this->assertArrayHasKey('exec', $parsed);
            $this->assertSame('com.phpunit.phpunit', $parsed['package']);
            $this->assertArrayNotHasKey('version', $parsed);
            $this->assertArrayNotHasKey('h', $parsed);
            $this->assertArrayNotHasKey('build', $parsed);
            $this->assertArrayNotHasKey('project', $parsed);
        }

        public function testDoubleDashIsAPassthroughSeparator(): void
        {
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['exec', '-p=com.example.tool', '--', '--help', 'file with spaces.txt']);

            $this->assertSame(['exec', '-p=com.example.tool'], $nccArguments);
            $this->assertSame(['--help', 'file with spaces.txt'], $passthrough);
        }

        public function testOnlyTheFirstSeparatorCounts(): void
        {
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['exec', '-p=x', '--args', 'a', '--args', '--', 'b']);

            $this->assertSame(['exec', '-p=x'], $nccArguments);
            $this->assertSame(['a', '--args', '--', 'b'], $passthrough);
        }

        public function testNoSeparatorKeepsEveryArgument(): void
        {
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['build', '--config=release']);

            $this->assertSame(['build', '--config=release'], $nccArguments);
            $this->assertNull($passthrough);
        }

        public function testSeparatorWithoutArgumentsGivesAnEmptyPassthrough(): void
        {
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['exec', '-p=x', '--args']);

            $this->assertSame(['exec', '-p=x'], $nccArguments);
            $this->assertSame([], $passthrough);
        }

        public function testArgsWithValueSyntaxIsNotASeparator(): void
        {
            // Only the bare separator counts, --args=... stays an ordinary (ncc) argument
            [$nccArguments, $passthrough] = Main::splitPassthroughArguments(['exec', '-p=x', '--args=foo']);

            $this->assertSame(['exec', '-p=x', '--args=foo'], $nccArguments);
            $this->assertNull($passthrough);
        }
    }
