<?php
    /*
     * Copyright (c) Nosial 2022-2025, all rights reserved.
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

    namespace ncc\Classes;

    use PHPUnit\Framework\TestCase;

    class PhpClassParserTest extends TestCase
    {
        public function testParsesClassLikeDefinitions(): void
        {
            $classes = PhpClassParser::parse('<?php namespace App\Models; class User {} interface HasName {} trait Greets {} enum Status { case Active; }');
            sort($classes);
            $this->assertSame(['App\Models\Greets', 'App\Models\HasName', 'App\Models\Status', 'App\Models\User'], $classes);
        }

        public function testKeepsNamespaceForMultipleClassesInOneFile(): void
        {
            // The namespace must not be reset when the body of the first class closes
            $classes = PhpClassParser::parse("<?php\nnamespace App;\n\nclass First\n{\n    public function a() {}\n}\n\nclass Second {}\n");
            $this->assertSame(['App\First', 'App\Second'], array_values($classes));
        }

        public function testBracketedNamespaces(): void
        {
            $classes = PhpClassParser::parse('<?php namespace A { class One {} } namespace B { class Two {} } namespace { class Three {} }');
            $this->assertSame(['A\One', 'B\Two', 'Three'], array_values($classes));
        }

        public function testIgnoresAnonymousClassesAndClassConstants(): void
        {
            $classes = PhpClassParser::parse('<?php namespace App; class Real { public function make() { $name = self::class; return new class {}; } }');
            $this->assertSame(['App\Real'], array_values($classes));
        }

        public function testInvalidCodeReturnsNoClasses(): void
        {
            $this->assertSame([], PhpClassParser::parse('just some text without php tags'));
        }
    }
