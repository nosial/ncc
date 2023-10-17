<?php
    /*
     * Copyright (c) Nosial 2022-2023, all rights reserved.
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

    namespace ncc\Classes\PhpExtension;

    use ncc\ThirdParty\nikic\PhpParser\Node;
    use ncc\ThirdParty\nikic\PhpParser\NodeVisitorAbstract;
    use ncc\Utilities\Console;

    class ExpressionTraverser extends NodeVisitorAbstract
    {
        /**
         * @var string|null
         */
        private ?string $package;

        /**
         * ExpressionTraverser constructor.
         *
         * @param string|null $package
         */
        public function __construct(?string $package)
        {
            $this->package = $package;
        }

        /**
         * @param Node $node
         * @return array|int|Node|null
         */
        public function leaveNode(Node $node): array|int|Node|null
        {
            if($node instanceof Node\Expr\Include_)
            {
                Console::outDebug(sprintf('Processing ExpressionTraverser on: %s', $node->getType()));
                $args = [$node->expr];

                if(!is_null($this->package))
                {
                    $args[] = new Node\Arg(new Node\Scalar\String_($this->package));
                }

                $types = [
                    Node\Expr\Include_::TYPE_INCLUDE => '\ncc\Classes\Runtime::runtimeInclude',
                    Node\Expr\Include_::TYPE_INCLUDE_ONCE => '\ncc\Classes\Runtime::runtimeIncludeOnce',
                    Node\Expr\Include_::TYPE_REQUIRE => '\ncc\Classes\Runtime::runtimeRequire',
                    Node\Expr\Include_::TYPE_REQUIRE_ONCE => '\ncc\Classes\Runtime::runtimeRequireOnce',
                ];

                return new Node\Expr\FuncCall(new Node\Name($types[$node->type]), $args);
            }

            return null;
        }
    }